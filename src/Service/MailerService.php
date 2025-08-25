<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

readonly class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string          $adminEmail = "grischko.kevin@gmail.com",
        private string          $fromEmail = "noreply@denz.ovh"
    ) {
    }

    public function sendWelcomeMail(string $userEmail): bool
    {
        try {
            $this->sendTemplatedEmail(
                $userEmail,
                "Bienvenue",
                'emails/welcome.html.twig',
                [
                    'userEmail' => $userEmail,
                    'loginUrl' => 'https://denz.ovh/login'
                ]
            );

            $this->logger->info('Email de bienvenue envoyé', [
                'recipient' => $userEmail
            ]);

            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de bienvenue', [
                'error' => $e->getMessage(),
                'recipient' => $userEmail
            ]);

            return false;
        }
    }

    public function sendAdminNotification(string $subject, string $message, array $context = []): bool
    {
        try {
            $this->sendTemplatedEmail(
                $this->adminEmail,
                '[ADMIN] ' . $subject,
                'emails/admin/notification.html.twig',
                [
                    'message' => $message,
                    'context' => $context,
                    'timestamp' => new \DateTime()
                ]
            );

            $this->logger->info('Notification admin envoyé', [
                'subject' => $subject,
                'context' => $context
            ]);

            return true;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de notification administrateur', [
                'error' => $e->getMessage(),
                'recipient' => $this->adminEmail,
                'subject' => $subject
            ]);

            return false;
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendTemplatedEmail(string $to, string $subject, string $template, array $context = [], ?string $from = null): void
    {
        $email = (new TemplatedEmail())
            ->from($from ?? $this->fromEmail)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->mailer->send($email);

        $this->logger->info('Email templated envoyé', [
            'recipient' => $to,
            'template' => $template,
            'subject' => $subject
        ]);
    }
}
