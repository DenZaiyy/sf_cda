<?php

namespace App\Controller;

use App\Service\OAuthProviderService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: "auth.oauth.login", methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app.home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: "auth.oauth.logout", methods: ['GET'])]
    public function logout(): void
    {
        throw new \LogicException('Don\'t forget to activate logout in your security.yaml');
    }

    #[Route('/oauth/connect/{service}', name: 'auth.oauth.connect', methods: ['GET'])]
    public function connect(string $service, ClientRegistry $clientRegistry, OAuthProviderService $OAuthProviderService): RedirectResponse
    {
        if (!$OAuthProviderService->isValidProvider($service)) {
            throw $this->createNotFoundException("Service OAuth inconnu");
        }

        $scopes = $OAuthProviderService->getScopes($service);

        return $clientRegistry->getClient($service)->redirect($scopes, []);
    }

    #[Route('/oauth/check/{service}', name: 'auth.oauth.check', methods: ['GET', 'POST'])]
    public function check(): Response
    {
        return new Response(status: 200);
    }
}
