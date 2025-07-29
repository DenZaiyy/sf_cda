<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

final readonly class OAuthRegistrationService
{
    public function __construct(
        private UserRepository $repository
    ) {
    }

    public function persist(ResourceOwnerInterface $resourceOwner): User
    {
        $user = new User();

        if ($resourceOwner instanceof GoogleUser) {
            $email = $this->requireEmail($resourceOwner->getEmail());
            $id = $resourceOwner->getId();

            $user
                ->setEmail($email)
                ->setGoogleId(is_scalar($id) ? (string) $id : null);
        } elseif ($resourceOwner instanceof GithubResourceOwner) {
            $email = $this->requireEmail($resourceOwner->getEmail());
            $id = $resourceOwner->getId();

            $user
                ->setEmail($email)
                ->setGithubId(is_scalar($id) ? (string) $id : null);
        }

        $this->repository->add($user, true);
        return $user;
    }

    private function requireEmail(?string $email): string
    {
        if ($email === null) {
            throw new \LogicException('Email is required for OAuth authentication.');
        }
        return $email;
    }
}
