<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\OAuthProviderService;
use PHPUnit\Framework\TestCase;

class OAuthProviderServiceTest extends TestCase
{
    private OAuthProviderService $service;

    protected function setUp(): void
    {
        $this->service = new OAuthProviderService();
    }

    public function testIsValidProviderReturnsTrueForKnownProviders(): void
    {
        $this->assertTrue($this->service->isValidProvider('google'));
        $this->assertTrue($this->service->isValidProvider('github'));
    }

    public function testIsValidProviderReturnsFalseForUnknownProvider(): void
    {
        $this->assertFalse($this->service->isValidProvider('facebook'));
    }

    public function testGetScopesReturnsCorrectScopes(): void
    {
        $this->assertSame([], $this->service->getScopes('google'));
        $this->assertSame(['user:email'], $this->service->getScopes('github'));
    }

    public function testGetScopesThrowsExceptionForInvalidProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider non reconnu : facebook');
        $this->service->getScopes('facebook');
    }

    public function testGetAvailableProviders(): void
    {
        $expected = ['google', 'github'];
        $this->assertSame($expected, $this->service->getAvailableProviders());
    }
}
