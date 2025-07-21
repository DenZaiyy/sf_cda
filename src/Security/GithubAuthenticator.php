<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GithubAuthenticator extends AbstractOAuthAuthenticator
{
    protected string $serviceName = 'github';

    protected function getUserFromResourceOwner(ResourceOwnerInterface $resourceOwner, UserRepository $repository): ?User
    {
        if (!$resourceOwner instanceof GithubResourceOwner) {
            throw new \RuntimeException('expected github user');
        }

        if (true !== ($resourceOwner->toArray()['email_verified'] ?? null)) {
            throw new AuthenticationException('email not verified');
        }

        return $repository->findOneBy([
            'github_id' => $resourceOwner->getId(),
            'email' => $resourceOwner->getEmail(),
        ]);
    }
}
