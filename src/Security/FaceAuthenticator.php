<?php

namespace App\Security;

use App\Entity\User;
use App\Service\FaceRecognitionService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class FaceAuthenticator extends AbstractAuthenticator
{
    public const FACE_VERIFY_ROUTE = 'app_face_verify';
    public const FACE_LOGIN_SESSION_KEY = '_face_login_user_id';

    public function __construct(
        private FaceRecognitionService $faceRecognitionService,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function supports(Request $request): bool
    {
        // Only authenticate on POST requests with face_encoding
        // But skip the enrollment submit route (user is already authenticated)
        $route = $request->attributes->get('_route');
        if ($route === 'app_face_enroll_submit') {
            return false;
        }
        
        // This allows the GET request to show the camera page
        return $request->isMethod('POST') && $request->request->has('face_encoding');
    }

    public function authenticate(Request $request): Passport
    {
        $faceEncoding = $request->request->get('face_encoding');
        
        if (!$faceEncoding) {
            throw new CustomUserMessageAuthenticationException('No face encoding provided.');
        }

        // Decode the JSON encoding
        $encoding = json_decode($faceEncoding, true);
        
        if (!$encoding || !is_array($encoding)) {
            throw new CustomUserMessageAuthenticationException('Invalid face encoding format.');
        }

        // Validate encoding
        if (!$this->faceRecognitionService->isValidEncoding($encoding)) {
            throw new CustomUserMessageAuthenticationException('Invalid face encoding.');
        }

        // Try to find user by face
        $user = $this->faceRecognitionService->findUserByFace($encoding);
        
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Face not recognized. Please try again or login with email/password.');
        }

        // Check if user is active
        if (!$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('Your account is blocked.');
        }

        // Store user ID in session for post-verification
        $request->getSession()->set(self::FACE_LOGIN_SESSION_KEY, $user->getId());

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn() => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $user = $token->getUser();

        if ($user instanceof User) {
            // Check if 2FA is enabled for this user
            if ($user->is2faEnabled()) {
                // Store user ID in session for 2FA verification
                $request->getSession()->set('_2fa_user_id', $user->getId());
                
                // Redirect to 2FA verification
                return new RedirectResponse($this->urlGenerator->generate('app_2fa_verify'));
            }

            // Clear any target path to ensure we always go to home after login
            $request->getSession()->remove('_security.main.target_path');

            // Redirect based on user role
            if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
            }

            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): ?RedirectResponse
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
