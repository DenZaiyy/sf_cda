<?php

declare(strict_types=1);

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OAuthProviderServiceTest extends WebTestCase
{
    public function testGoogleOAuthRedirect(): void
    {
        $client = static::createClient();

        $client->request('GET', '/oauth/connect/google');

        self::assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com', $location);
        $this->assertStringContainsString('oauth2', $location);
    }

    public function testGitHubOAuthRedirect(): void
    {
        $client = static::createClient();

        $client->request('GET', '/oauth/connect/github');

        self::assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('github.com', $location);
        $this->assertStringContainsString('oauth', $location);
    }
}
