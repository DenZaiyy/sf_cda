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
        $user = match (true) {
            $resourceOwner instanceof GoogleUser => (new User())
                ->setEmail($resourceOwner->getEmail())
                ->setGoogleId($resourceOwner->getId()),
            $resourceOwner instanceof GithubResourceOwner => (new User())
                ->setEmail($resourceOwner->getEmail())
                ->setGithubId($resourceOwner->getId()),
        };

        $this->repository->add($user, true);
        return $user;
    }
}
