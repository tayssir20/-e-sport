<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class FaceRecognitionService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Compare two face encodings and return similarity score (0-1)
     * Higher score means more similar faces
     */
    public function compareFaces(array $encoding1, array $encoding2): float
    {
        if (count($encoding1) !== count($encoding2)) {
            return 0.0;
        }

        // Calculate Euclidean distance
        $distance = 0.0;
        for ($i = 0; $i < count($encoding1); $i++) {
            $distance += pow($encoding1[$i] - $encoding2[$i], 2);
        }
        $distance = sqrt($distance);

        // Convert distance to similarity score (0-1)
        // Using exponential decay: similarity = e^(-distance / 2)
        // This gives ~0.6 similarity for distance of 1, ~0.36 for distance of 2, etc.
        $similarity = exp(-$distance / 2);

        return $similarity;
    }

    /**
     * Verify if the provided face encoding matches any user
     */
    public function verifyFace(array $providedEncoding, User $user): bool
    {
        if (!$user->isFaceEnabled() || !$user->getFaceEncoding()) {
            return false;
        }

        $storedEncoding = json_decode($user->getFaceEncoding(), true);
        
        if (!$storedEncoding) {
            return false;
        }

        $similarity = $this->compareFaces($providedEncoding, $storedEncoding);
        
        // Threshold: 0.5 similarity is a good balance between security and usability
        return $similarity >= 0.5;
    }

    /**
     * Find a user by face encoding (for initial login)
     */
    public function findUserByFace(array $providedEncoding): ?User
    {
        $users = $this->entityManager->getRepository(User::class)->findBy([
            'isFaceEnabled' => true,
        ]);

        foreach ($users as $user) {
            if ($this->verifyFace($providedEncoding, $user)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Save face encoding for a user
     */
    public function saveFaceEncoding(User $user, array $encoding): void
    {
        $user->setFaceEncoding(json_encode($encoding));
        $user->setIsFaceEnabled(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Remove face encoding for a user
     */
    public function removeFaceEncoding(User $user): void
    {
        $user->setFaceEncoding(null);
        $user->setIsFaceEnabled(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Validate that the encoding array is valid
     */
    public function isValidEncoding(array $encoding): bool
    {
        // face-api.js returns 128-dimensional face descriptors
        return count($encoding) === 128 && is_numeric($encoding[0]);
    }
}
