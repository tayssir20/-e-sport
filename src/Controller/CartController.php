<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CartItemRepository;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    private CartService $cartService;
    private ProductRepository $productRepository;
    private CartItemRepository $cartItemRepository;

    public function __construct(
        CartService $cartService,
        ProductRepository $productRepository,
        CartItemRepository $cartItemRepository
    ) {
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->cartItemRepository = $cartItemRepository;
    }

    /**
     * Display cart page
     */
    #[Route('/', name: 'app_cart_index', methods: ['GET'])]
    public function index(): Response
    {
        // Check if user is logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
$user = $this->getUser();
$cart = $this->cartService->getCart($user);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'items' => $cart->getItems(),
            'total' => $this->cartService->getCartTotal($cart),
            'totalItems' => $this->cartService->getTotalItems($cart),
        ]);
    }

    /**
     * Add product to cart
     */
    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request): JsonResponse
    {
        $this->productRepository->find($product->getId());
        
        // if AJAX call comes from an anonymous user we want a JSON response, not a redirect
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You must be logged in to add items to the cart'
            ], 401);
        }

      /** @var \App\Entity\User $user */
$user = $this->getUser();
$cart = $this->cartService->getCart($user);

        // Get quantity from request (default 1)
        $data = json_decode($request->getContent(), true);
        $quantity = $data['quantity'] ?? 1;

        // Check stock availability
        if ($product->getStock() < $quantity) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Insufficient stock available'
            ], 400);
        }

        try {
            $this->cartService->addProduct($cart, $product, $quantity);

            return new JsonResponse([
                'success' => true,
                'message' => 'Product added to cart successfully',
                'cartTotal' => $this->cartService->getTotalItems($cart)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error adding product to cart'
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $cartItem = $this->cartItemRepository->find($id);

        if (!$cartItem) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        // Verify cart belongs to current user
        if ($cartItem->getCart()->getUser() !== $this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        $quantity = $data['quantity'] ?? 1;

        // Check stock
        if ($cartItem->getProduct()->getStock() < $quantity) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Insufficient stock available'
            ], 400);
        }

        try {
            $this->cartService->updateQuantity($cartItem, $quantity);
            $cart = $cartItem->getCart();

            return new JsonResponse([
                'success' => true,
                'message' => 'Quantity updated successfully',
                'itemSubtotal' => $cartItem->getSubtotal(),
                'cartTotal' => $cart->getTotal(),
                'totalItems' => $this->cartService->getTotalItems($cart)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error updating quantity'
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $cartItem = $this->cartItemRepository->find($id);

        if (!$cartItem) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        // Verify cart belongs to current user
        if ($cartItem->getCart()->getUser() !== $this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $cart = $cartItem->getCart();
            $this->cartService->removeItem($cartItem);

            return new JsonResponse([
                'success' => true,
                'message' => 'Item removed from cart',
                'cartTotal' => $cart->getTotal(),
                'totalItems' => $this->cartService->getTotalItems($cart)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error removing item'
            ], 500);
        }
    }

    /**
     * Clear entire cart
     */
    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
$user = $this->getUser();
$cart = $this->cartService->getCart($user);

        try {
            $this->cartService->clearCart($cart);

            return new JsonResponse([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error clearing cart'
            ], 500);
        }
    }

    /**
     * Get cart data (for AJAX calls)
     */
    #[Route('/data', name: 'app_cart_data', methods: ['GET'])]
    public function getData(): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse([
                'totalItems' => 0,
                'total' => 0
            ]);
        }

        /** @var \App\Entity\User $user */
$user = $this->getUser();
$cart = $this->cartService->getCart($user);

        return new JsonResponse([
            'totalItems' => $this->cartService->getTotalItems($cart),
            'total' => $cart->getTotal()
        ]);
    }
    
}