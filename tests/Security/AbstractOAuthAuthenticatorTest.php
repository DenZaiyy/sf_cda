<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\AbstractOAuthAuthenticator;
use App\Security\OAuthRegistrationService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AbstractOAuthAuthenticatorTest extends TestCase
{
    private ClientRegistry&MockObject $clientRegistry;
    private RouterInterface&MockObject $router;
    private UserRepository&MockObject $repository;
    private OAuthRegistrationService $registrationService;
    private OAuth2ClientInterface&MockObject $oauthClient;
    private ConcreteOAuthAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->clientRegistry = $this->createMock(ClientRegistry::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->repository = $this->createMock(UserRepository::class);
        $this->registrationService = new OAuthRegistrationService($this->repository);
        $this->oauthClient = $this->createMock(OAuth2ClientInterface::class);

        $this->authenticator = new ConcreteOAuthAuthenticator(
            $this->clientRegistry,
            $this->router,
            $this->repository,
            $this->registrationService
        );
    }

    public function testSupportsReturnsTrueForCorrectRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'auth.oauth.check');
        $request->query->set('service', 'test');

        $result = $this->authenticator->supports($request);

        $this->assertTrue($result);
    }

    public function testSupportsReturnsFalseForIncorrectRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'other.route');

        $result = $this->authenticator->supports($request);

        $this->assertFalse($result);
    }

    public function testSupportsReturnsFalseForIncorrectService(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'auth.oauth.check');
        $request->query->set('service', 'wrong_service');

        $result = $this->authenticator->supports($request);

        $this->assertFalse($result);
    }

    public function testOnAuthenticationSuccessWithTargetPath(): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set('_security.main.target_path', '/dashboard');
        $request->setSession($session);
        
        $token = $this->createMock(TokenInterface::class);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/dashboard', $response->headers->get('Location'));
    }

    public function testOnAuthenticationSuccessWithoutTargetPath(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        
        $token = $this->createMock(TokenInterface::class);
        
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('app.home')
            ->willReturn('/home');

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/home', $response->headers->get('Location'));
    }

    public function testOnAuthenticationFailure(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        
        $exception = new AuthenticationException('Test error');
        
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('auth.oauth.check', ['service' => 'test'])
            ->willReturn('/auth/oauth/check/test');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/auth/oauth/check/test', $response->headers->get('Location'));
        $this->assertSame($exception, $request->getSession()->get('_security.403_error'));
    }

    public function testAuthenticateWithExistingUser(): void
    {
        $accessToken = new AccessToken(['access_token' => 'test_token']);
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);
        $user = new User();
        $user->setEmail('test@example.com');

        $this->clientRegistry
            ->expects($this->atLeastOnce())
            ->method('getClient')
            ->with('test')
            ->willReturn($this->oauthClient);

        $this->oauthClient
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->oauthClient
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($resourceOwner);

        $this->authenticator->setMockUser($user);

        $passport = $this->authenticator->authenticate(new Request());

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateWithNewUser(): void
    {
        $accessToken = new AccessToken(['access_token' => 'test_token']);
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);

        $this->clientRegistry
            ->expects($this->atLeastOnce())
            ->method('getClient')
            ->with('test')
            ->willReturn($this->oauthClient);

        $this->oauthClient
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $this->oauthClient
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($resourceOwner);

        $this->authenticator->setMockUser(null);
        $this->authenticator->setMockNewUser(true); // Tell the test helper to create a user

        $passport = $this->authenticator->authenticate(new Request());

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateThrowsExceptionWithInvalidToken(): void
    {
        $this->clientRegistry
            ->expects($this->atLeastOnce())
            ->method('getClient')
            ->with('test')
            ->willReturn($this->oauthClient);

        $this->oauthClient
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token.');

        $this->authenticator->authenticate(new Request());
    }
}

class ConcreteOAuthAuthenticator extends AbstractOAuthAuthenticator
{
    protected string $serviceName = 'test';
    private ?User $mockUser = null;
    private bool $createNewUser = false;

    public function setMockUser(?User $user): void
    {
        $this->mockUser = $user;
    }

    public function setMockNewUser(bool $create): void
    {
        $this->createNewUser = $create;
    }

    protected function getUserFromResourceOwner(ResourceOwnerInterface $resourceOwner, UserRepository $repository): ?User
    {
        if ($this->mockUser === null && $this->createNewUser) {
            $newUser = new User();
            $newUser->setEmail('test@example.com');
            return $newUser;
        }
        return $this->mockUser;
    }
}
