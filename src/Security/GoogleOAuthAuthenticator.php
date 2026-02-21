<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Google;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleOAuthAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private Google $googleProvider;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        string $googleClientId,
        string $googleClientSecret,
        string $redirectUrl
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;

        $this->googleProvider = new Google([
            'clientId' => $googleClientId,
            'clientSecret' => $googleClientSecret,
            'redirectUri' => $redirectUrl,
        ]);
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_google_callback'
            && $request->query->has('code');
    }

    public function authenticate(Request $request): Passport
    {
        $code = $request->query->get('code');

        try {
            // Exchange the authorization code for an access token
            $accessToken = $this->googleProvider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            // Get the user's information from Google
            $resourceOwner = $this->googleProvider->getResourceOwner($accessToken);
            $googleUser = $resourceOwner->toArray();

            $googleId = $googleUser['id'];
            $email = $googleUser['email'];
            $name = $googleUser['name'] ?? $googleUser['given_name'] ?? 'User';

            // Try to find existing user by Google ID
            $user = $this->userRepository->findOneBy(['googleOAuthId' => $googleId]);

            if (!$user) {
                // Check if user exists by email
                $user = $this->userRepository->findOneBy(['email' => $email]);

                if ($user) {
                    // Link existing user to Google account
                    $user->setGoogleOAuthId($googleId);
                    $user->setOauthProvider('google');
                } else {
                    // Create new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setNom($name);
                    $user->setGoogleOAuthId($googleId);
                    $user->setOauthProvider('google');
                    // Set a random password for OAuth users
                    $user->setPassword(bin2hex(random_bytes(16)));
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            return new SelfValidatingPassport(
                new UserBadge($user->getUserIdentifier())
            );
        } catch (\Exception $e) {
            throw new AuthenticationException('Failed to authenticate with Google: ' . $e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect to home after successful login
        return new RedirectResponse('/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        return new RedirectResponse('/login?error=' . urlencode($message));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Redirect to Google OAuth authorization
        $authorizationUrl = $this->googleProvider->getAuthorizationUrl([
            'scope' => ['email', 'profile'],
        ]);

        return new RedirectResponse($authorizationUrl);
    }
}
