<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\MailerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;

class MailerServiceTest extends TestCase
{
    private MailerService $service;

    public function setUp(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')->willThrowException(new TransportException('Error d\'envoi'));
        $logger = $this->createMock(LoggerInterface::class);
        $this->service = new MailerService($mailer, $logger);
    }

    public function testIsValidSendWelcomeMailReturnTrueForValidEmail(): void
    {
        $userMail = "test@gmail.com";
        $this->assertTrue($this->service->sendWelcomeMail($userMail));
    }

    public function testIsValidSendWelcomeMailReturnFalseForInvalidEmail(): void
    {
        $invalidMail = "test";
        $this->assertFalse($this->service->sendWelcomeMail($invalidMail));
        $this->assertFalse($this->service->sendWelcomeMail($invalidMail));
    }

    public function testIsValidSendAdminNotificationReturnTrue(): void
    {
        $subject = "Nouvelle connexion";
        $message = "Une nouvelle connexion as eu lieu sur le site, informations ....";
        $context = [
            'IP' => $_SERVER['REMOTE_ADDR'],
        ];

        $this->assertTrue($this->service->sendAdminNotification($subject, $message, $context));
    }

    public function testIsValidSendAdminNotificationReturnFalse(): void
    {
        $subject = "Nouvelle connexion";
        $message = "Une nouvelle connexion as eu lieu sur le site, informations ....";
        $context = [];

        $adminEmail = "test";

        $this->assertFalse($this->service->sendAdminNotification($subject, $message, $context, $adminEmail));
    }
}
