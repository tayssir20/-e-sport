<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAthenticateurAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator, private EntityManagerInterface $entityManager) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function($userIdentifier) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);
                if (!$user || !$user->isActive()) {
                    throw new CustomUserMessageAuthenticationException('Your account is blocked.');
                }
                return $user;
            }),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $user = $token->getUser();

        // Check if 2FA is enabled for this user
        if (is_object($user) && $user instanceof User && $user->is2faEnabled()) {
            // Store user ID in session for 2FA verification
            $request->getSession()->set('_2fa_user_id', $user->getId());
            
            // Redirect to 2FA verification
            return new RedirectResponse($this->urlGenerator->generate('app_2fa_verify'));
        }

        // Clear any target path to ensure we always go to home after login
        $request->getSession()->remove('_security.main.target_path');

        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        // Always redirect to home page after successful login
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
