<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    public function testIndexWithoutLogged(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        self::assertResponseStatusCodeSame(301, "You are not logged in.");
    }
}
