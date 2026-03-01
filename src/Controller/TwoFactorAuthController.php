<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TwoFactorAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TwoFactorAuthController extends AbstractController
{
    #[Route('/2fa/setup', name: 'app_2fa_setup')]
    public function setup(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService,
        EntityManagerInterface $entityManager,
        ?LoggerInterface $logger = null
    ): Response {
        $user = null;
        try {
            $user = $this->getUser();
            
            if (!$user instanceof User) {
                return $this->redirectToRoute('app_login');
            }

            if ($user->is2faEnabled()) {
                $this->addFlash('info', 'Two-factor authentication is already enabled.');
                return $this->redirectToRoute('app_home');
            }

            if (!$user->getGoogle2faSecret()) {
                $secret = $twoFactorAuthService->generateSecret();
                $user->setGoogle2faSecret($secret);
                $entityManager->flush();
            }

            $qrCodeImage = $twoFactorAuthService->getQRCodeImage($user);
            $secret = $user->getGoogle2faSecret();

            if ($request->isMethod('POST')) {
                $csrfToken = $request->request->get('_csrf_token');
                
                if (!$this->isCsrfTokenValid('2fa_setup', $csrfToken)) {
                    $this->addFlash('error', 'Invalid security token. Please try again.');
                } else {
                    $code = $request->request->get('2fa_code');
                    
                    if (empty($code)) {
                        $this->addFlash('error', 'Please enter a verification code.');
                    } elseif ($twoFactorAuthService->verifyCode($user, $code)) {
                        $twoFactorAuthService->enable2FA($user);
                        $this->addFlash('success', 'Two-factor authentication has been enabled!');
                        return $this->redirectToRoute('app_home');
                    } else {
                        $this->addFlash('error', 'Invalid verification code. Please try again.');
                    }
                }
            }

            return $this->render('two_factor_auth/setup.html.twig', [
                'qrCodeImage' => $qrCodeImage,
                'secret' => $secret,
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            $userId = null;
            if ($user instanceof User) {
                $userId = $user->getId();
            }
            if ($logger) {
                $logger->error('2FA Setup Error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'user_id' => $userId
                ]);
            }
            
            $this->addFlash('error', 'An error occurred while setting up 2FA. Please try again later.');
            return $this->redirectToRoute('app_profile');
        }
    }

    #[Route('/2fa/verify', name: 'app_2fa_verify')]
    public function verify(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $session = $request->getSession();

        if (!$user instanceof User || !$user->is2faEnabled()) {
            $this->addFlash('error', 'Invalid request. Please login again.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->get('2fa_code');

            if ($twoFactorAuthService->verifyCode($user, $code)) {
                $session->remove('_2fa_user_id');

                $token = new UsernamePasswordToken(
                    $user,
                    'main',
                    $user->getRoles()
                );

                $tokenStorage->setToken($token);
                $session->set('_security_main', serialize($token));

                $this->addFlash('success', 'Two-factor authentication verified successfully!');
                return $this->redirectToRoute('app_home');
            } else {
                $this->addFlash('error', 'Invalid verification code. Please try again.');
            }
        }

        return $this->render('two_factor_auth/verify.html.twig');
    }

    #[Route('/2fa/disable', name: 'app_2fa_disable')]
    public function disable(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user->is2faEnabled()) {
            $this->addFlash('info', 'Two-factor authentication is not enabled.');
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password', '');
            
            // Verify the password before disabling 2FA
            if (!$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Invalid password. Please try again.');
                return $this->redirectToRoute('app_2fa_disable');
            }
            
            $twoFactorAuthService->disable2FA($user);
            
            $this->addFlash('success', 'Two-factor authentication has been disabled.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('two_factor_auth/disable.html.twig');
    }
}
