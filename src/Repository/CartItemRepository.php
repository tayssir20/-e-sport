<?php

namespace App\Repository;

use App\Entity\CartItem;
use App\Entity\Cart;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /**
     * Find a cart item by cart and product
     */
    public function findByCartAndProduct(Cart $cart, Product $product): ?CartItem
    {
        return $this->findOneBy([
            'cart' => $cart,
            'product' => $product
        ]);
    }

    /**
     * Save cart item
     */
    public function save(CartItem $cartItem, bool $flush = true): void
    {
        $this->getEntityManager()->persist($cartItem);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove cart item
     */
    public function remove(CartItem $cartItem, bool $flush = true): void
    {
        $this->getEntityManager()->remove($cartItem);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
