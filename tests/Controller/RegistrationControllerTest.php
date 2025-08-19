<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Ensure we have a clean database
        $container = static::getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');
        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();

        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);
        $this->userRepository = $userRepository;

        foreach ($this->userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();
    }

    public function testRegister(): void
    {
        // Register a new user
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Register');

        $this->client->submitForm('Register', [
            'registration_form[email]' => 'me@example.com',
            'registration_form[plainPassword][first]' => 'password',
            'registration_form[plainPassword][second]' => 'password',
            'registration_form[agreeTerms]' => true,
        ]);

        // Ensure the response redirects after submitting the form, the user exists, and is not verified
        // self::assertResponseRedirects('/');  @TODO: set the appropriate path that the user is redirected to.
        $allUsers = $this->userRepository->findAll();
        self::assertCount(1, $allUsers);

        $user = $allUsers[0];
        self::assertInstanceOf(User::class, $user);
        self::assertFalse($user->isVerified());

        // Ensure the verification email was sent
        // Use either assertQueuedEmailCount() || assertEmailCount() depending on your mailer setup
        // self::assertQueuedEmailCount(1);
        self::assertEmailCount(1);

        $messages = self::getMailerMessages();
        self::assertCount(1, $messages);
        self::assertEmailAddressContains($messages[0], 'from', 'info@denzaiyy.fr');
        self::assertEmailAddressContains($messages[0], 'to', 'me@example.com');
        self::assertEmailTextBodyContains($messages[0], 'This link will expire in 1 hour.');

        // Login the new user
        $this->client->followRedirect();
        $this->client->loginUser($user);

        // Get the verification link from the email
        /** @var TemplatedEmail $templatedEmail */
        $templatedEmail = $messages[0];
        $messageBody = $templatedEmail->getHtmlBody();
        self::assertIsString($messageBody);

        preg_match('#(http://localhost/verify/email.+)">#', $messageBody, $resetLink);

        // "Click" the link and see if the user is verified
        $this->client->request('GET', $resetLink[1]);
        $this->client->followRedirect();

        /** @var UserRepository $freshUserRepository */
        $freshUserRepository = static::getContainer()->get(UserRepository::class);
        $verifiedUsers = $freshUserRepository->findAll();
        self::assertCount(1, $verifiedUsers);

        $verifiedUser = $verifiedUsers[0];
        self::assertInstanceOf(User::class, $verifiedUser);
        self::assertTrue($verifiedUser->isVerified());
    }
}
