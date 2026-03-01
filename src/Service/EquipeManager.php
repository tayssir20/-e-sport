<?php

namespace App\Service;

use App\Entity\Equipe;

class EquipeManager
{
    public function validate(Equipe $equipe): bool
    {
        // Règle 1 : Le nom est obligatoire
        if (empty($equipe->getNom())) {
            throw new \InvalidArgumentException("Le nom de l'équipe ne peut pas être vide");
        }

        // Règle 2 : Le nom ne doit pas dépasser 100 caractères
        if (strlen($equipe->getNom()) > 100) {
            throw new \InvalidArgumentException("Le nom ne peut pas dépasser 100 caractères");
        }

        // Règle 3 : Une équipe doit avoir un propriétaire
        if ($equipe->getOwner() === null) {
            throw new \InvalidArgumentException("Une équipe doit avoir un propriétaire");
        }

        // Règle 4 : Le nombre maximum de membres doit être entre 1 et 100
        if ($equipe->getMaxMembers() < 1 || $equipe->getMaxMembers() > 100) {
            throw new \InvalidArgumentException("Le nombre maximum de membres doit être entre 1 et 100");
        }

        return true;
    }
}
