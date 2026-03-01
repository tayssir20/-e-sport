<?php
// src/Repository/WishlistRepository.php

namespace App\Repository;

use App\Entity\Wishlist;
use App\Entity\User;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WishlistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wishlist::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->join('w.product', 'p')
            ->addSelect('p')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isInWishlist(User $user, Product $product): bool
    {
        return (bool) $this->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);
    }
}