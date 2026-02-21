<?php

namespace App\Controller;

use League\OAuth2\Client\Provider\Google;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleOAuthController extends AbstractController
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    private function getGoogleProvider(): Google
    {
        return new Google([
            'clientId' => $this->params->get('google_oauth_client_id'),
            'clientSecret' => $this->params->get('google_oauth_client_secret'),
            'redirectUri' => $this->params->get('google_oauth_redirect_url'),
        ]);
    }

    #[Route('/oauth/google', name: 'app_google_oauth', methods: ['GET'])]
    public function redirectToGoogle(): RedirectResponse
    {
        $googleProvider = $this->getGoogleProvider();
        $authorizationUrl = $googleProvider->getAuthorizationUrl([
            'scope' => ['email', 'profile'],
        ]);

        // Store state in session for security
        $_SESSION['oauth2state'] = $googleProvider->getState();

        return new RedirectResponse($authorizationUrl);
    }

    #[Route('/oauth/google/callback', name: 'app_google_callback', methods: ['GET'])]
    public function handleGoogleCallback(Request $request): Response
    {
        // The authenticator will handle the authentication
        // This route is just for the callback and security processing
        return new RedirectResponse('/');
    }
}
    
