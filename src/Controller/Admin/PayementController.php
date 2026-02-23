<?php
// src/Controller/Admin/PayementController.php
// src/Controller/Admin/PayementController.php
namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/payements')] // just the path, no name here
class PayementController extends AbstractController
{
    #[Route('/', name: 'admin_payement')] // set the route name here
    public function index(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findBy([], ['createdAt' => 'DESC']); // latest first

        $totalOrders = count($orders);
        $pendingOrders = count(array_filter($orders, fn($o) => $o->getStatus() === 'pending'));
        $paidOrders = count(array_filter($orders, fn($o) => $o->getStatus() === 'paid'));
        $canceledOrders = count(array_filter($orders, fn($o) => $o->getStatus() === 'canceled'));

        return $this->render('payment/Admin/order.html.twig', [
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'paidOrders' => $paidOrders,
            'canceledOrders' => $canceledOrders,
        ]);
    }
}
