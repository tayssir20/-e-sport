<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\MatchGame;
use App\Entity\Tournoi;
use App\Repository\MatchGameRepository;
use App\Repository\TournoiRepository;
use Doctrine\ORM\EntityManagerInterface;
use TournamentGenerator\Tournament;


class MatchGeneratorService
{
    /**
     * Nombre minimum d'équipes pour déclencher la génération automatique.
     */
    private const MIN_EQUIPES = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TournoiRepository $tournoiRepository,
        private MatchGameRepository $matchGameRepository,
    ) {
    }

    /**
    
     * @return int Le nombre de matchs générés (0 si les conditions ne sont pas remplies)
     */
    public function generateIfReady(Tournoi $tournoi): int
    {
        $equipes = $this->tournoiRepository->getEquipesInscrites($tournoi);

        if (count($equipes) < self::MIN_EQUIPES) {
            return 0;
        }

        $existingMatches = $this->matchGameRepository->findByTournoi($tournoi);
        if (count($existingMatches) > 0) {
            return 0;
        }

        return $this->doGenerate($tournoi, $equipes);
    }

    /**
     *
     * @return int Le nombre de matchs générés
     */
    public function regenerate(Tournoi $tournoi): int
    {
        $equipes = $this->tournoiRepository->getEquipesInscrites($tournoi);

        if (count($equipes) < self::MIN_EQUIPES) {
            return 0;
        }

        // Supprimer les anciens matchs du tournoi
        $existingMatches = $this->matchGameRepository->findByTournoi($tournoi);
        foreach ($existingMatches as $match) {
            $this->entityManager->remove($match);
        }
        $this->entityManager->flush();

        return $this->doGenerate($tournoi, $equipes);
    }

    /**
     * Logique de génération via heroyt/tournament-generator.
     *
     * @param Equipe[] $equipes
     */
    private function doGenerate(Tournoi $tournoi, array $equipes): int
    {
        $tournament = new Tournament($tournoi->getNom());
        $round = $tournament->round('Phase de poules');
        $group = $round->group('Groupe principal');

        // Map: tournament-generator team id => Equipe entity
        $teamMap = [];
        foreach ($equipes as $equipe) {
            $group->team($equipe->getNom(), $equipe->getId());
            $teamMap[$equipe->getId()] = $equipe;
        }

        // Générer les matchs en round-robin (chaque équipe joue contre chaque autre)
        $games = $group->genGames();

        // Date de départ : demain à 18h, espacés d'1 heure
        $startDate = new \DateTime('+1 day');
        $startDate->setTime(18, 0);
        $matchInterval = new \DateInterval('PT1H');

        $generatedCount = 0;
        foreach ($games as $game) {
            $teams = $game->getTeams();
            if (count($teams) < 2) {
                continue;
            }

            $team1Id = $teams[0]->getId();
            $team2Id = $teams[1]->getId();

            if (!isset($teamMap[$team1Id]) || !isset($teamMap[$team2Id])) {
                continue;
            }

            $matchGame = new MatchGame();
            $matchGame->setEquipe1($teamMap[$team1Id]);
            $matchGame->setEquipe2($teamMap[$team2Id]);
            $matchGame->setTournoi($tournoi);
            $matchGame->setScoreTeam1(0);
            $matchGame->setScoreTeam2(0);
            $matchGame->setStatut('scheduled');
            $matchGame->setDateMatch(clone $startDate);

            $this->entityManager->persist($matchGame);
            $startDate->add($matchInterval);
            $generatedCount++;
        }

        $this->entityManager->flush();

      

        return $generatedCount;
    }
}
