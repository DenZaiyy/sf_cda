<?php

namespace App\Service;

use App\Entity\User;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class UserRegistrationService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface      $em,
        private EmailVerifier               $emailVerifier,
    ) {
    }

    public function register(User $user, string $plainPassword): bool
    {
        $regex = "/^(?=.*\d)(?=.*[!-\/:-@[-`{-~À-ÿ§µ²°£])(?=.*[a-z])(?=.*[A-Z])(?=.*[A-Za-z]).{12,32}$/u";
        $match = preg_match($regex, $plainPassword);

        if (!$match || strlen($plainPassword) < 6) {
            return false;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->em->persist($user);
        $this->em->flush();

        $this->sendConfirmationEmail($user);

        return true;
    }

    private function sendConfirmationEmail(User $user): void
    {
        $this->emailVerifier->sendEmailConfirmation(
            'app.verify.email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('info@denzaiyy.fr', 'DenZaiyy'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }
}
