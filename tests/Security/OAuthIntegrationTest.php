<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\GithubAuthenticator;
use App\Security\GoogleAuthenticator;
use App\Security\OAuthRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OAuthIntegrationTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function getMyContainer(): \Symfony\Component\DependencyInjection\ContainerInterface
    {
        if (!self::$booted) {
            self::bootKernel();
        }
        return static::getContainer();
    }

    private function initializeDatabase(): void
    {
        if (!isset($this->entityManager)) {
            $container = $this->getMyContainer();
            $this->entityManager = $container->get(EntityManagerInterface::class);
            $this->userRepository = $container->get(UserRepository::class);
            $this->cleanDatabase();
        }
    }

    private function cleanDatabase(): void
    {
        foreach ($this->userRepository->findAll() as $user) {
            $this->entityManager->remove($user);
        }
        $this->entityManager->flush();
    }

    public function testOAuthConnectRouteRedirectsToProvider(): void
    {
        $client = static::createClient();

        $client->request('GET', '/oauth/connect/google');

        $this->assertResponseStatusCodeSame(302);

        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com', $location);
        $this->assertStringContainsString('oauth2', $location);
    }

    public function testOAuthConnectWithInvalidProvider(): void
    {
        $client = static::createClient();

        $client->request('GET', '/oauth/connect/invalid');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testOAuthCheckRouteWithoutAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/oauth/check/google');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testGoogleAuthenticatorWithExistingUser(): void
    {
        $this->initializeDatabase();

        $user = new User();
        $user->setEmail('existing@gmail.com');
        $user->setGoogleId('google-123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $container = $this->getMyContainer();

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $oauthClient = $this->createMock(OAuth2ClientInterface::class);
        $accessToken = new AccessToken(['access_token' => 'test_token']);

        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser->method('toArray')->willReturn(['email_verified' => true]);
        $googleUser->method('getId')->willReturn('google-123');
        $googleUser->method('getEmail')->willReturn('existing@gmail.com');

        $clientRegistry->method('getClient')->willReturn($oauthClient);
        $oauthClient->method('getAccessToken')->willReturn($accessToken);
        $oauthClient->method('fetchUserFromToken')->willReturn($googleUser);

        $authenticator = new GoogleAuthenticator(
            $clientRegistry,
            $container->get('router'),
            $this->userRepository,
            $container->get(\App\Security\OAuthRegistrationService::class)
        );

        $passport = $authenticator->authenticate(new \Symfony\Component\HttpFoundation\Request());

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testGithubAuthenticatorWithNewUser(): void
    {
        $this->initializeDatabase();

        $container = $this->getMyContainer();

        $clientRegistry = $this->createMock(ClientRegistry::class);
        $oauthClient = $this->createMock(OAuth2ClientInterface::class);
        $accessToken = new AccessToken(['access_token' => 'test_token']);

        $githubUser = $this->createMock(GithubResourceOwner::class);
        $githubUser->method('toArray')->willReturn(['email_verified' => true]);
        $githubUser->method('getId')->willReturn('github-456');
        $githubUser->method('getEmail')->willReturn('new@github.com');

        $clientRegistry->method('getClient')->willReturn($oauthClient);
        $oauthClient->method('getAccessToken')->willReturn($accessToken);
        $oauthClient->method('fetchUserFromToken')->willReturn($githubUser);

        $authenticator = new GithubAuthenticator(
            $clientRegistry,
            $container->get('router'),
            $this->userRepository,
            $container->get(\App\Security\OAuthRegistrationService::class)
        );

        $passport = $authenticator->authenticate(new \Symfony\Component\HttpFoundation\Request());

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);

        $newUser = $this->userRepository->findOneBy(['email' => 'new@github.com']);
        $this->assertNotNull($newUser);
        $this->assertSame('github-456', $newUser->getGithubId());
    }

    public function testOAuthLoginPageAccessible(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/oauth/connect/google"]');
        $this->assertSelectorExists('a[href="/oauth/connect/github"]');
    }

    public function testLogoutRedirectsWhenNotAuthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/logout');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testAuthenticatedUserRedirectsFromLogin(): void
    {
        $client = static::createClient();
        $this->initializeDatabase();

        $user = new User();
        $user->setEmail('authenticated@example.com');
        $user->setGoogleId('auth-123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $client->loginUser($user);

        $client->request('GET', '/login');

        $this->assertResponseRedirects('/');
    }

    public function testSecurityConfigurationForOAuth(): void
    {
        $container = $this->getMyContainer();

        $this->assertTrue($container->has(GoogleAuthenticator::class));
        $this->assertTrue($container->has(GithubAuthenticator::class));
        $this->assertTrue($container->has(OAuthRegistrationService::class));
        $this->assertTrue($container->has('knpu.oauth2.client.google'));
        $this->assertTrue($container->has('knpu.oauth2.client.github'));
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->cleanDatabase();
        }
        parent::tearDown();
    }
}
