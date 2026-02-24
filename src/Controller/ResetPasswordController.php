<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(private ResetPasswordHelperInterface $resetPasswordHelper) {}

    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer,
                $entityManager
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ?string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the token in logs
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $e->getReason(),
                'If you are having trouble clicking the password reset link, copy and pasting the URL into your web browser'
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it when used.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $entityManager->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('reset_password/check_email.html.twig');
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (\SymfonyCasts\Bundle\ResetPassword\Exception\TooManyPasswordRequestsException $e) {
            // Show user when they can retry
            $retryAfter = $e->getRetryAfter();
            $minutes = ceil($retryAfter / 60);
            $this->addFlash('reset_password_error', sprintf(
                'You have already requested a password reset email. Please wait %d minute(s) before requesting another one.',
                $minutes
            ));
            return $this->redirectToRoute('app_check_email');
        } catch (\SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', 'Token generation failed: ' . $e->getMessage() . ' - Reason: ' . $e->getReason());
            return $this->redirectToRoute('app_check_email');
        } catch (\Exception $e) {
            $this->addFlash('reset_password_error', 'There was a problem generating your reset token: ' . $e->getMessage());
            return $this->redirectToRoute('app_check_email');
        }

        // Create email with reset link
        $tokenLifetime = $this->resetPasswordHelper->getTokenLifetime();
        
        $email = (new TemplatedEmail())
            ->from(new Address('rajhiaziz2@gmail.com', 'E-Sport Manager'))
            ->to($user->getEmail())
            ->subject('Your password reset request');
        $email->getHeaders()->addTextHeader('X-Transport', 'reset');
        $email
            ->htmlTemplate('reset_password/email.html.twig')
            ->textTemplate('reset_password/email.txt.twig')
            ->context([
                'resetToken' => $resetToken->getToken(),
                'tokenLifetime' => $tokenLifetime,
            ])
        ;

        try {
            $mailer->send($email);
            $this->addFlash('reset_password_success', 'Password reset email sent successfully to ' . $user->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('reset_password_error', 'There was a problem sending the email. Please try again later. Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_check_email');
        }

        // Store token in session
        $this->storeTokenInSession($resetToken->getToken());
        
        return $this->redirectToRoute('app_check_email');
    }
}
