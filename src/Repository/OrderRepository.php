<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $order, bool $flush = true): void
    {
        $this->getEntityManager()->persist($order);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $order, bool $flush = true): void
    {
        $this->getEntityManager()->remove($order);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
     public function getMonthlyRevenue(): array
    {
        return $this->createQueryBuilder('o')
            ->select("DATE_FORMAT(o.createdAt, '%Y-%m') as month, SUM(o.totalPrice) as total")
            ->where('o.status = :status')
            ->setParameter('status', 'paid')
            ->andWhere('o.createdAt >= :date')
            ->setParameter('date', new \DateTime('-12 months'))
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ✅ Top 5 produits les plus vendus
    public function getTopProducts(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('oi.productName as name, SUM(oi.quantity) as totalQty')
            ->from(OrderItem::class, 'oi')
            ->join('oi.order', 'o')
            ->where('o.status = :status')
            ->setParameter('status', 'paid')
            ->groupBy('oi.productName')
            ->orderBy('totalQty', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
}
