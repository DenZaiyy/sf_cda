<?php

namespace App\Service;

final class OAuthProviderService
{
    public const SCOPES = [
        'google' => [],
        'github' => ['user:email'],
    ];

    public function isValidProvider(string $provider): bool
    {
        return array_key_exists($provider, self::SCOPES);
    }

    /**
     * @param string $provider
     * @return list<string>
     */
    public function getScopes(string $provider): array
    {
        if (!$this->isValidProvider($provider)) {
            throw new \InvalidArgumentException("Provider non reconnu : $provider");
        }

        return self::SCOPES[$provider];
    }

    /**
     * @return list<string>
     */
    public function getAvailableProviders(): array
    {
        return array_keys(self::SCOPES);
    }
}
