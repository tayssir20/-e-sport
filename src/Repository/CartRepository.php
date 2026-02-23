<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /**
     * Find or create a cart for a user
     */
    public function findOrCreateForUser(User $user): Cart
    {
        $cart = $this->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->getEntityManager()->persist($cart);
            $this->getEntityManager()->flush();
        }

        return $cart;
    }

    /**
     * Save cart
     */
    public function save(Cart $cart, bool $flush = true): void
    {
        $this->getEntityManager()->persist($cart);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove cart
     */
    public function remove(Cart $cart, bool $flush = true): void
    {
        $this->getEntityManager()->remove($cart);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    
}
