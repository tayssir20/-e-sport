<?php

namespace App\Controller;

use App\Service\PubgApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PubgStatsController extends AbstractController
{
    private PubgApiService $pubgApi;

    public function __construct(PubgApiService $pubgApi)
    {
        $this->pubgApi = $pubgApi;
    }

    #[Route('/stats/pubg', name: 'app_pubg_stats', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->pubgApi->isConfigured()) {
            return $this->render('game_stats/coming_soon.html.twig', [
                'game' => 'PUBG',
                'icon' => '🪂',
                'color' => '#f97316',
                'api' => 'PUBG API',
                'apiConfigured' => false,
                'apiKeyUrl' => 'https://developer.pubg.com/',
                'features' => [
                    'Rang Ranked',
                    'Victoires (Chicken Dinners)',
                    'K/D ratio',
                    'Top 10 finishes',
                    'Dégâts moyens',
                    'Distance parcourue'
                ]
            ]);
        }

        return $this->render('pubg_stats/index.html.twig');
    }

    #[Route('/stats/pubg/search', name: 'app_pubg_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $playerName = $request->request->get('player_name');
        $platform = $request->request->get('platform', 'steam');
        
        if (!$playerName) {
            $this->addFlash('error', 'Veuillez entrer un nom de joueur');
            return $this->redirectToRoute('app_pubg_stats');
        }
        
        return $this->redirectToRoute('app_pubg_player', [
            'playerName' => $playerName,
            'platform' => $platform,
        ]);
    }

    #[Route('/stats/pubg/player/{platform}/{playerName}', name: 'app_pubg_player', methods: ['GET'])]
    public function player(string $playerName, string $platform = 'steam'): Response
    {
        $stats = $this->pubgApi->getPlayerStats($playerName, $platform);

        if (!$stats || !$stats['player']) {
            $this->addFlash('error', 'Joueur non trouvé. Vérifiez le nom et la plateforme.');
            return $this->redirectToRoute('app_pubg_stats');
        }

        return $this->render('pubg_stats/player.html.twig', [
            'player' => $stats['player'],
            'seasonStats' => $stats['seasonStats'],
            'rankedStats' => $stats['rankedStats'],
            'platform' => $platform,
        ]);
    }
}
