<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Profile\ChangePasswordForm;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile', name: 'app.profile.')]
#[IsGranted('ROLE_USER', message: 'You are not logged in.', statusCode: Response::HTTP_FORBIDDEN)]
final class ProfileController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/change-password', name: 'change.password', methods: ['POST', 'GET'])]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, MailerService $mailerService): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            $this->addFlash('danger', 'User are not logged in.');
            return $this->redirectToRoute('auth.oauth.login');
        }

        $form = $this->createForm(ChangePasswordForm::class, $currentUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            if (!is_string($newPassword) || !is_string($currentPassword)) {
                $this->addFlash('danger', 'Current password is wrong.');
                return $this->redirectToRoute('app.profile.change.password');
            }

            if ($passwordHasher->isPasswordValid($currentUser, $currentPassword)) {
                $newPassword = $passwordHasher->hashPassword($currentUser, $newPassword);
                $currentUser->setPassword($newPassword);
                $entityManager->persist($currentUser);
                $entityManager->flush();

                $this->addFlash('success', 'Your password has been changed successfully.');
                $mailerService->sendAdminNotification("Password changed", sprintf("L'utilisateur %s a changer son mot de passe", $currentUser->getEmail()));
            } else {
                $this->addFlash('danger', 'Your current password is incorrect.');
            }

            return $this->redirectToRoute('app.profile.index');
        }

        return $this->render('profile/change-password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
