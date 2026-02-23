<?php

namespace App\Controller;

use App\Service\ValorantApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ValorantStatsController extends AbstractController
{
    private ValorantApiService $valorantApi;

    public function __construct(ValorantApiService $valorantApi)
    {
        $this->valorantApi = $valorantApi;
    }

    #[Route('/stats/valorant', name: 'app_valorant_stats', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('valorant_stats/index.html.twig');
    }

    #[Route('/stats/valorant/search', name: 'app_valorant_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $riotId = $request->request->get('riot_id');
        
        if (!$riotId || !str_contains($riotId, '#')) {
            $this->addFlash('error', 'Format invalide. Utilisez: NomJoueur#TAG');
            return $this->redirectToRoute('app_valorant_stats');
        }

        [$gameName, $tagLine] = explode('#', $riotId, 2);
        
        return $this->redirectToRoute('app_valorant_player', [
            'gameName' => $gameName,
            'tagLine' => $tagLine,
        ]);
    }

    #[Route('/stats/valorant/player/{gameName}/{tagLine}', name: 'app_valorant_player', methods: ['GET'])]
    public function player(string $gameName, string $tagLine): Response
    {
        $stats = $this->valorantApi->getPlayerStats($gameName, $tagLine);

        if (!$stats) {
            $this->addFlash('error', 'Joueur non trouvé. Vérifiez le nom et le tag.');
            return $this->redirectToRoute('app_valorant_stats');
        }

        // Les matchs sont déjà dans le bon format depuis Henrik API
        $matchDetails = $stats['matchHistory'] ?? [];

        return $this->render('valorant_stats/player.html.twig', [
            'account' => $stats['account'],
            'mmr' => $stats['mmr'],
            'matchDetails' => $matchDetails,
        ]);
    }
}
