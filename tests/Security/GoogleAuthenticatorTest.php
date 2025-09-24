<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\GoogleAuthenticator;
use App\Security\OAuthRegistrationService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GoogleAuthenticatorTest extends TestCase
{
    private ClientRegistry&MockObject $clientRegistry;
    private RouterInterface&MockObject $router;
    private UserRepository&MockObject $repository;
    private OAuthRegistrationService $registrationService;
    private GoogleAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->clientRegistry = $this->createMock(ClientRegistry::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->repository = $this->createMock(UserRepository::class);
        $this->registrationService = new OAuthRegistrationService($this->repository);

        $this->authenticator = new GoogleAuthenticator(
            $this->clientRegistry,
            $this->router,
            $this->repository,
            $this->registrationService
        );
    }

    public function testGetUserFromResourceOwnerWithVerifiedEmail(): void
    {
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['email_verified' => true]);

        $googleUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn('google-id-123');

        $googleUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('user@gmail.com');

        $user = new User();
        $user->setEmail('user@gmail.com');
        $user->setGoogleId('google-id-123');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'google_id' => 'google-id-123',
                'email' => 'user@gmail.com'
            ])
            ->willReturn($user);

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $result = $method->invoke($this->authenticator, $googleUser, $this->repository);

        $this->assertSame($user, $result);
    }

    public function testGetUserFromResourceOwnerWithUnverifiedEmail(): void
    {
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['email_verified' => false]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('email not verified');

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $method->invoke($this->authenticator, $googleUser, $this->repository);
    }

    public function testGetUserFromResourceOwnerWithMissingEmailVerification(): void
    {
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser
            ->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('email not verified');

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $method->invoke($this->authenticator, $googleUser, $this->repository);
    }

    public function testGetUserFromResourceOwnerThrowsExceptionForWrongResourceOwner(): void
    {
        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('expected google user');

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $method->invoke($this->authenticator, $resourceOwner, $this->repository);
    }

    public function testGetUserFromResourceOwnerReturnsNullWhenUserNotFound(): void
    {
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['email_verified' => true]);

        $googleUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn('google-new-456');

        $googleUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('newuser@gmail.com');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'google_id' => 'google-new-456',
                'email' => 'newuser@gmail.com'
            ])
            ->willReturn(null);

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $result = $method->invoke($this->authenticator, $googleUser, $this->repository);

        $this->assertNull($result);
    }

    public function testServiceNameIsGoogle(): void
    {
        $reflection = new \ReflectionClass($this->authenticator);
        $property = $reflection->getProperty('serviceName');

        $this->assertSame('google', $property->getValue($this->authenticator));
    }

    public function testGetUserFromResourceOwnerWithNumericId(): void
    {
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['email_verified' => true]);

        $googleUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn(123456789);

        $googleUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('numeric@gmail.com');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'google_id' => 123456789,
                'email' => 'numeric@gmail.com'
            ])
            ->willReturn(null);

        $reflection = new \ReflectionClass($this->authenticator);
        $method = $reflection->getMethod('getUserFromResourceOwner');

        $result = $method->invoke($this->authenticator, $googleUser, $this->repository);

        $this->assertNull($result);
    }
}
