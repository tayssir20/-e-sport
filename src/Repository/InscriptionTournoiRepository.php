<?php

namespace App\Repository;

use App\Entity\InscriptionTournoi;
use App\Entity\Equipe;
use App\Entity\Tournoi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InscriptionTournoi>
 */
class InscriptionTournoiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InscriptionTournoi::class);
    }

    /**
     * @return InscriptionTournoi[]
     */
    public function findByTournoi(Tournoi $tournoi): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.tournoi = :tournoi')
            ->setParameter('tournoi', $tournoi)
            ->orderBy('i.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InscriptionTournoi[]
     */
    public function findByEquipe(Equipe $equipe): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.equipe = :equipe')
            ->setParameter('equipe', $equipe)
            ->orderBy('i.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
