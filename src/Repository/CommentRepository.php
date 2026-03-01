<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    // Add your custom query methods here
    public function findByBlog($blog, $limit = null)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.blog = :blog')
            ->setParameter('blog', $blog)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecentComments($limit = 10)
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    public function search(string $term)
{
    return $this->createQueryBuilder('c')
                ->where('c.content LIKE :term')
                ->setParameter('term', '%'.$term.'%')
                ->getQuery()
                ->getResult();
}
}