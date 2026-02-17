<?php

namespace App\Repository;

use App\Entity\Equipe;
use App\Entity\Tournoi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournoi>
 */
class TournoiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournoi::class);
    }

    /**
     * Récupère toutes les équipes inscrites au tournoi (inscription_tournoi + relation equipe_tournoi).
     */
    public function getEquipesInscrites(Tournoi $tournoi): array
    {
        $em = $this->getEntityManager();
        $ids = [];
        try {
            $conn = $em->getConnection();
            $sql = 'SELECT equipe_id FROM inscription_tournoi WHERE tournoi_id = :tid ORDER BY date_inscription ASC';
            $ids = $conn->fetchFirstColumn($sql, ['tid' => $tournoi->getId()]);
        } catch (\Throwable $e) {
            // table peut ne pas exister
        }
        $fromRelation = $tournoi->getEquipes()->toArray();
        $seen = array_flip($ids);
        foreach ($fromRelation as $eq) {
            $id = $eq->getId();
            if (!isset($seen[$id])) {
                $ids[] = $id;
            }
        }
        if (empty($ids)) {
            return [];
        }
        $equipes = $em->getRepository(Equipe::class)->findBy(['id' => array_unique($ids)]);
        usort($equipes, fn ($a, $b) => array_search($a->getId(), $ids) <=> array_search($b->getId(), $ids));
        return $equipes;
    }

    //    /**
    //     * @return Tournoi[] Returns an array of Tournoi objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tournoi
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function findAllWithEquipes(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.jeu', 'j')
            ->addSelect('j')
            ->leftJoin('t.equipes', 'e')
            ->addSelect('e')
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneWithJeu(int $id): ?Tournoi
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.jeu', 'j')
            ->addSelect('j')
            ->leftJoin('t.equipes', 'e')
            ->addSelect('e')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Retourne les équipes inscrites au tournoi qui ont des places disponibles
     * et où l'utilisateur n'est pas déjà membre ou propriétaire.
     */
    public function getEquipesRejoignables(Tournoi $tournoi, object $user): array
    {
        $equipesInscrites = $this->getEquipesInscrites($tournoi);
        $rejoignables = [];
        foreach ($equipesInscrites as $equipe) {
            if ($equipe->getOwner() === $user || $equipe->getMembers()->contains($user)) {
                continue;
            }
            if ($equipe->getMembers()->count() < $equipe->getMaxMembers()) {
                $rejoignables[] = $equipe;
            }
        }
        return $rejoignables;
    }
}
