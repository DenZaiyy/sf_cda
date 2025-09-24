<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\OAuthRegistrationService;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OAuthRegistrationServiceTest extends TestCase
{
    private UserRepository&MockObject $repository;
    private OAuthRegistrationService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepository::class);
        $this->service = new OAuthRegistrationService($this->repository);
    }

    public function testPersistWithGoogleUser(): void
    {
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('google@example.com');

        $googleUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn('google-123');

        $this->repository
            ->expects($this->once())
            ->method('add')
            ->with(
                $this->callback(fn (User $user): bool => $user->getEmail() === 'google@example.com'
                    && $user->getGoogleId() === 'google-123'
                    && $user->getGithubId() === null),
                true
            );

        $result = $this->service->persist($googleUser);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame('google@example.com', $result->getEmail());
        $this->assertSame('google-123', $result->getGoogleId());
        $this->assertNull($result->getGithubId());
    }

    public function testPersistWithGithubUser(): void
    {
        $githubUser = $this->createMock(GithubResourceOwner::class);
        $githubUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('github@example.com');

        $githubUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn('github-456');

        $this->repository
            ->expects($this->once())
            ->method('add')
            ->with(
                $this->callback(fn (User $user): bool => $user->getEmail() === 'github@example.com'
                    && $user->getGithubId() === 'github-456'
                    && $user->getGoogleId() === null),
                true
            );

        $result = $this->service->persist($githubUser);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame('github@example.com', $result->getEmail());
        $this->assertSame('github-456', $result->getGithubId());
        $this->assertNull($result->getGoogleId());
    }

    public function testPersistWithGoogleUserWithNullEmail(): void
    {
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Email is required for OAuth authentication.');

        $this->service->persist($googleUser);
    }

    public function testPersistWithGithubUserWithNullEmail(): void
    {
        $githubUser = $this->createMock(GithubResourceOwner::class);
        $githubUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Email is required for OAuth authentication.');

        $this->service->persist($githubUser);
    }

    public function testPersistWithNumericGoogleId(): void
    {
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('numeric@example.com');

        $googleUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn(12345);

        $this->repository
            ->expects($this->once())
            ->method('add')
            ->with(
                $this->callback(fn (User $user): bool => $user->getEmail() === 'numeric@example.com'
                    && $user->getGoogleId() === '12345'),
                true
            );

        $result = $this->service->persist($googleUser);

        $this->assertSame('12345', $result->getGoogleId());
    }

    public function testPersistWithNumericGithubId(): void
    {
        $githubUser = $this->createMock(GithubResourceOwner::class);
        $githubUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('numeric@github.com');

        $githubUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn(67890);

        $this->repository
            ->expects($this->once())
            ->method('add')
            ->with(
                $this->callback(fn (User $user): bool => $user->getEmail() === 'numeric@github.com'
                    && $user->getGithubId() === '67890'),
                true
            );

        $result = $this->service->persist($githubUser);

        $this->assertSame('67890', $result->getGithubId());
    }

    public function testPersistWithNonScalarId(): void
    {
        $googleUser = $this->createMock(GoogleUser::class);
        $googleUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn('array@example.com');

        $googleUser
            ->expects($this->once())
            ->method('getId')
            ->willReturn(['complex' => 'id']);

        $this->repository
            ->expects($this->once())
            ->method('add')
            ->with(
                $this->callback(fn (User $user): bool => $user->getEmail() === 'array@example.com'
                    && $user->getGoogleId() === null),
                true
            );

        $result = $this->service->persist($googleUser);

        $this->assertNull($result->getGoogleId());
    }

    public function testPersistWithUnsupportedResourceOwner(): void
    {
        $unsupportedResourceOwner = $this->createMock(ResourceOwnerInterface::class);

        $this->repository
            ->expects($this->once())
            ->method('add')
            ->with(
                $this->isInstanceOf(User::class),
                true
            );

        $result = $this->service->persist($unsupportedResourceOwner);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testRequireEmailWithValidEmail(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('requireEmail');

        $result = $method->invoke($this->service, 'valid@example.com');

        $this->assertSame('valid@example.com', $result);
    }

    public function testRequireEmailWithNullEmail(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('requireEmail');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Email is required for OAuth authentication.');

        $method->invoke($this->service, null);
    }
}
