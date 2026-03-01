<?php
// src/Repository/ProductRatingRepository.php

namespace App\Repository;

use App\Entity\ProductRating;
use App\Entity\User;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductRating::class);
    }

    public function findByProduct(Product $product): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->addSelect('u')
            ->where('r.product = :product')
            ->setParameter('product', $product)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUserRating(User $user, Product $product): ?ProductRating
    {
        return $this->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);
    }
}