<?php

namespace App\Service;

use App\Entity\Product;

class ProductManager
{
    public function validate(Product $product): bool
    {
        // Règle 1 : Le nom est obligatoire et doit avoir au moins 3 caractères
        if (empty($product->getName())) {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }

        if (strlen($product->getName()) < 3) {
            throw new \InvalidArgumentException('Le nom doit contenir au moins 3 caractères');
        }

        // Règle 2 : La description est obligatoire et doit avoir au moins 5 caractères
        if (empty($product->getDescription())) {
            throw new \InvalidArgumentException('La description est obligatoire');
        }

        if (strlen($product->getDescription()) < 5) {
            throw new \InvalidArgumentException('La description doit contenir au moins 5 caractères');
        }

        // Règle 3 : Le prix est obligatoire et doit être positif
        if ($product->getPrice() === null) {
            throw new \InvalidArgumentException('Le prix est obligatoire');
        }

        if ($product->getPrice() <= 0) {
            throw new \InvalidArgumentException('Le prix doit être un nombre positif');
        }

        // Règle 4 : Le stock est obligatoire et doit être positif
        if ($product->getStock() === null) {
            throw new \InvalidArgumentException('Le stock est obligatoire');
        }

        if ($product->getStock() <= 0) {
            throw new \InvalidArgumentException('Le stock doit être un nombre positif');
        }

        // Règle 5 : L'image est obligatoire et doit être une URL valide
        if (empty($product->getImage())) {
            throw new \InvalidArgumentException("L'image est obligatoire");
        }

        if (!filter_var($product->getImage(), FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Veuillez entrer une URL valide');
        }

        // Règle 6 : La catégorie est obligatoire
        if ($product->getCategory() === null) {
            throw new \InvalidArgumentException('Veuillez choisir une catégorie');
        }

        return true;
    }
}