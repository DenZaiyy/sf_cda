<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\GithubAuthenticator;
use App\Security\OAuthRegistrationService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GithubAuthenticatorTest extends TestCase
{
    private ClientRegistry&MockObject $clientRegistry;
    private RouterInterface&MockObject $router;
    private UserRepository&MockObject $repository;
    private OAuthRegistrationService $registrationService;
    private GithubAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->clientRegistry = $this->createMock(ClientRegistry::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->repository = $this->createMock(UserRepository::class);
        $this->registrationService = new OAuthRegistrationService($this->repository);

        $this->authenticator = new GithubAuthenticator(
            $this->clientRegistry,
            $this->router,
            $this->repository,
            $this->registrationService
        );
    }

    public function testGetUserFromResourceOwnerWithVerifiedEmail(): void
    {
        $githubResourceOwner = $this->createMock(GithubResourceOwner::class);
        $githubResourceOwner
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['email_verified' => true]);

        $githubResourceOwner
            ->expects($this->once())
            ->method('getId')
            ->willReturn('12345');

        $githubResourceOwner
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('test@example.com');

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setGithubId('12345');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'github_id' => '12345',
                'email' => 'test@example.com'
            ])
            ->willReturn($user);

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $result = $method->invoke($this->authenticator, $githubResourceOwner, $this->repository);

        $this->assertSame($user, $result);
    }

    public function testGetUserFromResourceOwnerWithUnverifiedEmail(): void
    {
        $githubResourceOwner = $this->createMock(GithubResourceOwner::class);
        $githubResourceOwner
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['email_verified' => false]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('email not verified');

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $method->invoke($this->authenticator, $githubResourceOwner, $this->repository);
    }

    public function testGetUserFromResourceOwnerWithMissingEmailVerification(): void
    {
        $githubResourceOwner = $this->createMock(GithubResourceOwner::class);
        $githubResourceOwner
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('email not verified');

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $method->invoke($this->authenticator, $githubResourceOwner, $this->repository);
    }

    public function testGetUserFromResourceOwnerThrowsExceptionForWrongResourceOwner(): void
    {
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('expected github user');

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $method->invoke($this->authenticator, $resourceOwner, $this->repository);
    }

    public function testGetUserFromResourceOwnerReturnsNullWhenUserNotFound(): void
    {
        $githubResourceOwner = $this->createMock(GithubResourceOwner::class);
        $githubResourceOwner
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['email_verified' => true]);

        $githubResourceOwner
            ->expects($this->once())
            ->method('getId')
            ->willReturn('67890');

        $githubResourceOwner
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('new@example.com');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'github_id' => '67890',
                'email' => 'new@example.com'
            ])
            ->willReturn(null);

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $result = $method->invoke($this->authenticator, $githubResourceOwner, $this->repository);

        $this->assertNull($result);
    }

    public function testServiceNameIsGithub(): void
    {
        $reflection = new \ReflectionClass($this->authenticator);
        $property = $reflection->getProperty('serviceName');

        $this->assertSame('github', $property->getValue($this->authenticator));
    }
}
