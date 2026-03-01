<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validate(User $user): bool
    {
        // Email obligatoire et valide
        if (empty($user->getEmail()) || 
            !filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        // Nom obligatoire minimum 3 caractères
        if (empty($user->getNom()) || strlen($user->getNom()) < 3) {
            throw new \InvalidArgumentException('Nom invalide');
        }

        // Mot de passe minimum 8 caractères
        if (empty($user->getPassword()) || strlen($user->getPassword()) < 8) {
            throw new \InvalidArgumentException('Mot de passe trop court');
        }

        return true;
    }
}