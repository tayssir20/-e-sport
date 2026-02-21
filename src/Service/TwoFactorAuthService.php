<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Psr\Log\LoggerInterface;

class TwoFactorAuthService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Generate a new TOTP secret for a user
     */
    public function generateSecret(): string
    {
        $totp = TOTP::create();
        return $totp->getSecret();
    }

    /**
     * Generate QR code URL for Google Authenticator
     */
    public function getQRCodeUrl(User $user): string
    {
        $secret = $user->getGoogle2faSecret();
        
        if (!$secret) {
            $secret = $this->generateSecret();
            $user->setGoogle2faSecret($secret);
            $this->entityManager->flush();
        }

        $totp = TOTP::create(
            $secret,
            30,
            'SHA1',
            6
        );

        $totp->setIssuer('E-Sport');
        $totp->setParameter('email', $user->getEmail());

        return $totp->getProvisioningUri();
    }

    /**
     * Generate QR code as SVG data URI
     * @return string Returns SVG data URI or empty string on failure
     */
    public function getQRCodeImage(User $user): string
    {
        $qrCodeUrl = $this->getQRCodeUrl($user);

        try {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $writer = new \BaconQrCode\Writer($renderer);
            
            $svg = $writer->writeString($qrCodeUrl);
            
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Exception $e) {
            // Log the error for debugging
            if ($this->logger) {
                $this->logger->error('Failed to generate QR code image: ' . $e->getMessage());
            }
            // Return empty string on failure - controller will handle this gracefully
            return '';
        }
    }

    /**
     * Verify the TOTP code
     */
    public function verifyCode(User $user, string $code): bool
    {
        $secret = $user->getGoogle2faSecret();
        
        if (!$secret) {
            return false;
        }

        $totp = TOTP::create(
            $secret,
            30,
            'SHA1',
            6
        );

        return $totp->verify($code);
    }

    /**
     * Enable 2FA for a user after verification
     */
    public function enable2FA(User $user): void
    {
        $user->setIs2faEnabled(true);
        $this->entityManager->flush();
    }

    /**
     * Disable 2FA for a user
     */
    public function disable2FA(User $user): void
    {
        $user->setIs2faEnabled(false);
        $user->setGoogle2faSecret(null);
        $this->entityManager->flush();
    }
}
