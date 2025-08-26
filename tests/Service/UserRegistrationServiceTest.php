<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Security\EmailVerifier;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationServiceTest extends TestCase
{
    private UserRegistrationService $service;

    public function setUp(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $emailVerifier = $this->createMock(EmailVerifier::class);
        $this->service = new UserRegistrationService($passwordHasher, $em, $emailVerifier);
    }

    public function testIsValidRegistrationPassword(): void
    {
        $user = new User();
        $user->setEmail("test-email@example.com");

        $this->assertTrue($this->service->register($user, "Test.267878!"));
    }

    public function testIsNotValidRegistrationPasswordStrength(): void
    {
        $user = new User();
        $user->setEmail('second-test@example.com');
        $password = "123";

        $this->assertFalse($this->service->register($user, $password));
    }
}
