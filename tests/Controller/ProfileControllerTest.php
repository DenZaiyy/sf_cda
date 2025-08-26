<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testIndexWithoutLogged(): void
    {
        $this->client->request('GET', '/profile/');

        // Votre contrôleur retourne 403 car vous utilisez IsGranted avec statusCode: HTTP_FORBIDDEN
        self::assertResponseStatusCodeSame(302);
        $this->client->followRedirect();
        $this->client->request('GET', '/login/');
        //self::assertSelectorTextContains('body', 'You are not logged in.');
    }

    public function testIndexWithLogged(): void
    {
        // Récupérer l'EntityManager via le service
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        /** @var UserRepository $userRepository */
        $userRepository = $entityManager->getRepository(User::class);

        // Utiliser findOneBy au lieu de findOneByEmail
        /** @var User|null $testUser */
        $testUser = $userRepository->findOneBy(['email' => 'test@example.com']);

        if (!$testUser) {
            $this->markTestSkipped('Test user not found in database');
        }

        $this->client->loginUser($testUser);
        $this->client->request('GET', '/profile/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Profile');
        self::assertRouteSame('app.profile.index');
    }
}
