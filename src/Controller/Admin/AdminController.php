<?php
namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        EntityManagerInterface $em,
        OrderRepository        $orderRepository,
        ProductRepository      $productRepository,
        UserRepository         $userRepository,
    ): Response {

        // ── Ventes par mois ────────────────────────────
        $monthlyData = $orderRepository->getMonthlyRevenue();
        $months      = array_map(fn($r) => $r['month'], $monthlyData);
        $revenues    = array_map(fn($r) => (float) $r['total'], $monthlyData);

        // ── Top 5 produits ─────────────────────────────
        $topProducts  = $orderRepository->getTopProducts();
        $productNames = array_map(fn($r) => $r['name'], $topProducts);
        $productQtys  = array_map(fn($r) => (int) $r['totalQty'], $topProducts);

        // ── Stats ──────────────────────────────────────
        $totalOrders  = $orderRepository->count(['status' => 'paid']);
        $totalRevenue = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.status = :s')
            ->setParameter('s', 'paid')
            ->getQuery()
            ->getSingleScalarResult();
        $totalProducts = $productRepository->count([]);
        $totalUsers    = $userRepository->count([]);

        return $this->render('admin/statisticsproduct.html.twig', [
            'months'         => json_encode($months),
            'revenues'       => json_encode($revenues),
            'productNames'   => json_encode($productNames),
            'productQtys'    => json_encode($productQtys),
            'total_orders'   => $totalOrders,
            'total_revenue'  => number_format($totalRevenue ?? 0, 2),
            'total_products' => $totalProducts,
            'total_users'    => $totalUsers,
        ]);
    }
}
