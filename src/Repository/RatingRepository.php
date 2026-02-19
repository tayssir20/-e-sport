<?php

namespace App\Repository;

use App\Entity\Blog;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 *
 * @method Rating|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rating|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rating[]    findAll()
 * @method Rating[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * Trouve la note d'un utilisateur pour un blog spécifique.
     */
    public function findOneByBlogAndUser(Blog $blog, User $user): ?Rating
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.blog = :blog')
            ->andWhere('r.user = :user')
            ->setParameter('blog', $blog)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les notes d'un blog.
     */
    public function findByBlog(Blog $blog): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.blog = :blog')
            ->setParameter('blog', $blog)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les notes d'un utilisateur.
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la moyenne des notes pour un blog.
     */
    public function getAverageRatingForBlog(Blog $blog): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.value) as average')
            ->andWhere('r.blog = :blog')
            ->setParameter('blog', $blog)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float) $result, 1) : 0.0;
    }

    /**
     * Compte le nombre de notes pour un blog.
     */
    public function countRatingsForBlog(Blog $blog): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.blog = :blog')
            ->setParameter('blog', $blog)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère la distribution des notes pour un blog.
     */
    public function getRatingDistributionForBlog(Blog $blog): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.value, COUNT(r.id) as count')
            ->andWhere('r.blog = :blog')
            ->setParameter('blog', $blog)
            ->groupBy('r.value')
            ->orderBy('r.value', 'ASC')
            ->getQuery()
            ->getResult();

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($results as $result) {
            $distribution[$result['value']] = (int) $result['count'];
        }

        return $distribution;
    }

    /**
     * Vérifie si un utilisateur a déjà noté un blog.
     */
    public function hasUserRatedBlog(Blog $blog, User $user): bool
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.blog = :blog')
            ->andWhere('r.user = :user')
            ->setParameter('blog', $blog)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Supprime toutes les notes d'un blog.
     */
    public function deleteByBlog(Blog $blog): void
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.blog = :blog')
            ->setParameter('blog', $blog)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime toutes les notes d'un utilisateur.
     */
    public function deleteByUser(User $user): void
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère les blogs les mieux notés.
     */
    public function findTopRatedBlogs(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->select('b', 'AVG(r.value) as avg_rating', 'COUNT(r.id) as rating_count')
            ->join('r.blog', 'b')
            ->groupBy('b.id')
            ->having('rating_count >= 1')
            ->orderBy('avg_rating', 'DESC')
            ->addOrderBy('rating_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
