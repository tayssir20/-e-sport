<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    public function __construct(
        private string $stripeSecretKey,
        private CartService $cartService,
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/create-checkout-session', name: 'payment_create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $cart = $this->cartService->getCart($user);

        if ($cart->getItems()->count() === 0) {
            return $this->json(['success' => false, 'message' => 'Cart is empty'], 400);
        }

        $data = json_decode($request->getContent(), true);

        // Validate required fields
        foreach (['firstName', 'lastName', 'email', 'address', 'city', 'postalCode', 'phone'] as $field) {
            if (empty($data[$field])) {
                return $this->json(['success' => false, 'message' => 'Missing field: ' . $field], 400);
            }
        }

        // Check stock
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getStock() < $item->getQuantity()) {
                return $this->json(['success' => false, 'message' => '"' . $item->getProduct()->getName() . '" is out of stock'], 400);
            }
        }

        // Create Order in database
        $order = new Order();
        $order->setUser($user);
        $order->setTotalPrice($cart->getTotal());
        $order->setFirstName($data['firstName']);
        $order->setLastName($data['lastName']);
        $order->setEmail($data['email']);
        $order->setAddress($data['address']);
        $order->setCity($data['city']);
        $order->setPostalCode($data['postalCode']);
        $order->setPhone($data['phone']);

        foreach ($cart->getItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setProductName($cartItem->getProduct()->getName());
            $orderItem->setProductPrice($cartItem->getProduct()->getPrice());
            $orderItem->setQuantity($cartItem->getQuantity());
            $order->addItem($orderItem);
        }

        $this->orderRepository->save($order);

        // Build Stripe line items
        $lineItems = [];
        foreach ($cart->getItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item->getProduct()->getName(),
                    ],
                    'unit_amount' => (int)($item->getProduct()->getPrice() * 100),
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        try {
            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $this->generateUrl('payment_success', ['orderId' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $this->generateUrl('payment_cancel', ['orderId' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'client_reference_id' => (string)$order->getId(),
                'customer_email' => $data['email'],
            ]);

            $order->setStripeSessionId($checkoutSession->id);
            $this->entityManager->flush();

            return $this->json(['success' => true, 'sessionId' => $checkoutSession->id]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/success/{orderId}', name: 'payment_success')]
    public function success(int $orderId): Response
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Order not found');
        }

        // Mark as paid
        $order->setStatus('paid');
        $order->setPaidAt(new \DateTimeImmutable());

        // Decrease stock
        foreach ($order->getItems() as $orderItem) {
            $product = $orderItem->getProduct();
            $product->setStock(max(0, $product->getStock() - $orderItem->getQuantity()));
        }

        $this->entityManager->flush();

        // Clear cart
        $cart = $this->cartService->getCart($this->getUser());
        $this->cartService->clearCart($cart);

        return $this->render('payment/payment_success.html.twig', ['order' => $order]);
    }

    #[Route('/cancel/{orderId}', name: 'payment_cancel')]
    public function cancel(int $orderId): Response
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Order not found');
        }

        $order->setStatus('cancelled');
        $this->entityManager->flush();

        return $this->render('payment/payment_cancel.html.twig', ['order' => $order]);
    }
}
