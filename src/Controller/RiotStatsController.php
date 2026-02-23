<?php

namespace App\Controller;

use App\Service\RiotApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RiotStatsController extends AbstractController
{
    #[Route('/stats/lol', name: 'app_riot_stats')]
    public function index(): Response
    {
        return $this->render('riot_stats/index.html.twig');
    }

    #[Route('/stats/lol/search', name: 'app_riot_stats_search', methods: ['POST'])]
    public function search(Request $request, RiotApiService $riotApiService): Response
    {
        $searchQuery = $request->request->get('summoner_name', '');
        
        // Parse le nom (format: GameName#TAG)
        $parts = explode('#', $searchQuery);
        if (count($parts) !== 2) {
            $this->addFlash('error', 'Format invalide. Utilisez: NomDuJoueur#TAG (ex: Player#EUW)');
            return $this->redirectToRoute('app_riot_stats');
        }

        [$gameName, $tagLine] = $parts;

        // Debug: Log la recherche
        error_log("Recherche: gameName='$gameName', tagLine='$tagLine'");

        $playerStats = $riotApiService->getPlayerStats($gameName, $tagLine);

        // Debug: Log le résultat
        error_log("Résultat playerStats: " . ($playerStats ? 'TROUVÉ' : 'NULL'));
        if ($playerStats) {
            error_log("PlayerStats keys: " . implode(', ', array_keys($playerStats)));
        }

        if (!$playerStats) {
            $this->addFlash('error', 'Joueur non trouvé. Vérifiez le nom et le tag.');
            return $this->redirectToRoute('app_riot_stats');
        }

        // Récupérer les détails des matchs
        $matches = [];
        foreach (array_slice($playerStats['matchHistory'], 0, 5) as $matchId) {
            $matchDetails = $riotApiService->getMatchDetails($matchId);
            if ($matchDetails) {
                $matches[] = $matchDetails;
            }
        }

        return $this->render('riot_stats/player.html.twig', [
            'playerStats' => $playerStats,
            'matches' => $matches,
            'searchQuery' => $searchQuery,
        ]);
    }
}
