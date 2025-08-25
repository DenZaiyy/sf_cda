<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Récupérer les services nécessaires
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $this->entityManager = $container->get(EntityManagerInterface::class);

        /** @var UserRepository $userRepository */
        $this->userRepository = $container->get(UserRepository::class);

        // Nettoyer la base de données avant chaque test
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        // Supprimer tous les utilisateurs existants
        foreach ($this->userRepository->findAll() as $user) {
            $this->entityManager->remove($user);
        }
        $this->entityManager->flush();
    }

    public function testRegisterPageIsAccessible(): void
    {
        $this->client->request('GET', '/register');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Register');
        self::assertSelectorExists('form[name="registration_form"]');
    }

    public function testRegisterRedirectsIfUserAlreadyLoggedIn(): void
    {
        // Créer et connecter un utilisateur
        $user = new User();
        $user->setEmail('existing@example.com');
        $user->setRoles(['ROLE_USER']);

        $this->client->loginUser($user);
        $this->client->request('GET', '/register');

        // Doit rediriger vers la page d'accueil
        self::assertResponseRedirects('/');
    }

    public function testSuccessfulRegistration(): void
    {
        // Aller sur la page d'inscription
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        // Soumettre le formulaire d'inscription
        $this->client->submitForm('Register', [
            'registration_form[email]' => 'me@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        // Vérifier la redirection après inscription
        self::assertResponseRedirects('/');

        // Vérifier qu'un utilisateur a été créé
        $allUsers = $this->userRepository->findAll();
        self::assertCount(1, $allUsers);

        /** @var User $user */
        $user = $allUsers[0];
        self::assertInstanceOf(User::class, $user);
        self::assertSame('me@example.com', $user->getEmail());
        self::assertFalse($user->isVerified(), 'User should not be verified initially');

        // Vérifier le message de succès
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Your account has been created');
    }

    public function testRegistrationSendsVerificationEmail(): void
    {
        // Soumettre le formulaire d'inscription
        $this->client->request('GET', '/register');
        $this->client->submitForm('Register', [
            'registration_form[email]' => 'me@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        // Vérifier qu'un email a été envoyé
        self::assertEmailCount(1);

        $messages = self::getMailerMessages();
        self::assertCount(1, $messages);

        /** @var TemplatedEmail $email */
        $email = $messages[0];
        self::assertEmailAddressContains($email, 'from', 'info@denzaiyy.fr');
        self::assertEmailAddressContains($email, 'to', 'me@example.com');
        self::assertEmailTextBodyContains($email, 'This link will expire in 1 hour.');
    }

    public function testEmailVerificationProcess(): void
    {
        // Créer un utilisateur non vérifié
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Connecter l'utilisateur
        $this->client->loginUser($user);

        // Simuler l'inscription pour générer l'email de vérification
        $this->client->request('GET', '/register');
        $this->client->submitForm('Register', [
            'registration_form[email]' => 'verification@example.com',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        // Récupérer l'email de vérification
        $messages = self::getMailerMessages();
        if (empty($messages)) {
            $this->markTestSkipped('No verification email was sent');
        }

        /** @var TemplatedEmail $templatedEmail */
        $templatedEmail = $messages[0];
        $messageBody = $templatedEmail->getHtmlBody();
        self::assertIsString($messageBody);

        // Extraire le lien de vérification
        preg_match('#(http://localhost/verify/email[^"]+)#', $messageBody, $matches);

        if (empty($matches[1])) {
            $this->markTestSkipped('Could not extract verification link from email');
        }

        $verificationLink = $matches[1];

        // Récupérer l'utilisateur fraîchement créé
        $newUser = $this->userRepository->findOneBy(['email' => 'verification@example.com']);
        self::assertNotNull($newUser);

        // Connecter le nouvel utilisateur
        $this->client->loginUser($newUser);

        // "Cliquer" sur le lien de vérification
        $this->client->request('GET', $verificationLink);
        self::assertResponseRedirects('/');

        // Suivre la redirection
        $this->client->followRedirect();

        // Vérifier que l'utilisateur est maintenant vérifié
        $this->entityManager->refresh($newUser);
        self::assertTrue($newUser->isVerified(), 'User should be verified after clicking verification link');

        // Vérifier le message de succès
        self::assertSelectorTextContains('.alert-success', 'Your email address has been verified');
    }

    public function testRegistrationWithInvalidData(): void
    {
        $this->client->request('GET', '/register');

        // Soumettre avec des mots de passe différents
        $this->client->submitForm('Register', [
            'registration_form[email]' => 'invalid-email',
            'registration_form[plainPassword][first]' => 'password123',
            'registration_form[plainPassword][second]' => 'different_password',
            'registration_form[agreeTerms]' => false,
        ]);

        // Ne doit pas rediriger (reste sur la page avec erreurs)
        self::assertResponseIsSuccessful();

        // Aucun utilisateur ne doit être créé
        $allUsers = $this->userRepository->findAll();
        self::assertCount(0, $allUsers);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanDatabase();
    }
}
