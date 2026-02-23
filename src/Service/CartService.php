<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;

class CartService
{
    private EntityManagerInterface $entityManager;
    private CartRepository $cartRepository;
    private CartItemRepository $cartItemRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        CartRepository $cartRepository,
        CartItemRepository $cartItemRepository
    ) {
        $this->entityManager = $entityManager;
        $this->cartRepository = $cartRepository;
        $this->cartItemRepository = $cartItemRepository;
    }

    /**
     * Get or create cart for user
     */
    public function getCart(User $user): Cart
    {
        return $this->cartRepository->findOrCreateForUser($user);
    }

    /**
     * Add product to cart
     */
    public function addProduct(Cart $cart, Product $product, int $quantity = 1): void
    {
        // Check if product already exists in cart
        $cartItem = $this->cartItemRepository->findByCartAndProduct($cart, $product);

        if ($cartItem) {
            // Update quantity
            $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
        } else {
            // Create new cart item
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            
            $cart->addItem($cartItem);
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity(CartItem $cartItem, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($cartItem);
            return;
        }

        $cartItem->setQuantity($quantity);
        $cartItem->getCart()->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Remove item from cart
     */
    public function removeItem(CartItem $cartItem): void
    {
        $cart = $cartItem->getCart();
        $cart->removeItem($cartItem);
        
        $this->entityManager->remove($cartItem);
        $this->entityManager->flush();
    }

    /**
     * Clear all items from cart
     */
    public function clearCart(Cart $cart): void
    {
        foreach ($cart->getItems() as $item) {
            $this->entityManager->remove($item);
        }
        
        $cart->clear();
        $this->entityManager->flush();
    }

    /**
     * Get cart total
     */
    public function getCartTotal(Cart $cart): float
    {
        return $cart->getTotal();
    }

    /**
     * Get total items count
     */
    public function getTotalItems(Cart $cart): int
    {
        $total = 0;
        foreach ($cart->getItems() as $item) {
            $total += $item->getQuantity();
        }
        return $total;
    }
}
