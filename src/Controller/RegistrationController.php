<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\EmailVerifier;
use App\Service\MailerService;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly UserRegistrationService $userRegistrationService,
        private readonly MailerService $mailerService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/register', name: 'app.register')]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app.home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('plainPassword')->getData();

            if (is_string($password)) {
                $this->addFlash('success', 'Your account has been created.');
                $this->userRegistrationService->register($user, $password);
                return $this->redirectToRoute('app.home');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @throws ORMException
     */
    #[Route('/verify/email', name: 'app.verify.email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var User $user */
            $user = $this->getUser();

            // Vérification de l'email - cela met à jour isVerified=true et persiste
            $this->emailVerifier->handleEmailConfirmation($request, $user);
            dd('test');

            // Important : Rafraîchir l'entité depuis la base de données
            $this->entityManager->refresh($user);

            // Maintenant envoyer l'email de bienvenue
            $welcomeSent = $this->mailerService->sendWelcomeMail($user->getEmail());

            if ($welcomeSent) {
                $this->addFlash('success', 'Votre adresse email a été vérifiée. Un email de bienvenue vous a été envoyé.');
            } else {
                $this->addFlash('success', 'Votre adresse email a été vérifiée.');
                $this->addFlash('warning', 'L\'email de bienvenue n\'a pas pu être envoyé.');
            }
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app.register');
        }

        return $this->redirectToRoute('app.home');
    }
}
