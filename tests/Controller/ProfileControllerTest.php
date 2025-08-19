<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    private $client = null;
    protected function setUp(): void
    {
        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => 'test@example.com',
            'PHP_AUTH_PW'   => 'password',
        ]);
    }

    public function testIndexWithoutLogged(): void
    {
        $this->client->request('GET', '/profile');

        self::assertResponseStatusCodeSame(301, "You are not logged in.");
    }

    public function testIndexWithLogged(): void
    {
        $this->client->request('GET', '/profile');
        self::assertResponseIsSuccessful();
    }
}
