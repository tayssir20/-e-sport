<?php

namespace App\Controller;

use App\Service\SteamApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CS2StatsController extends AbstractController
{
    private SteamApiService $steamApi;

    public function __construct(SteamApiService $steamApi)
    {
        $this->steamApi = $steamApi;
    }

    #[Route('/stats/cs2', name: 'app_cs2_stats', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->steamApi->isConfigured()) {
            return $this->render('game_stats/coming_soon.html.twig', [
                'game' => 'Counter-Strike 2',
                'icon' => '🔫',
                'color' => '#f59e0b',
                'api' => 'Steam API',
                'apiConfigured' => false,
                'apiKeyUrl' => 'https://steamcommunity.com/dev/apikey',
                'features' => [
                    'Rang Premier',
                    'K/D ratio',
                    'Headshot percentage',
                    'Heures jouées',
                    'Armes favorites',
                    'Maps préférées'
                ]
            ]);
        }

        return $this->render('cs2_stats/index.html.twig');
    }

    #[Route('/stats/cs2/search', name: 'app_cs2_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $steamId = $request->request->get('steam_id');
        
        if (!$steamId) {
            $this->addFlash('error', 'Veuillez entrer un Steam ID ou vanity URL');
            return $this->redirectToRoute('app_cs2_stats');
        }

        // Si ce n'est pas un nombre, essayer de résoudre la vanity URL
        if (!is_numeric($steamId)) {
            $resolvedId = $this->steamApi->resolveVanityUrl($steamId);
            if (!$resolvedId) {
                $this->addFlash('error', 'Steam ID ou vanity URL invalide');
                return $this->redirectToRoute('app_cs2_stats');
            }
            $steamId = $resolvedId;
        }
        
        return $this->redirectToRoute('app_cs2_player', ['steamId' => $steamId]);
    }

    #[Route('/stats/cs2/player/{steamId}', name: 'app_cs2_player', methods: ['GET'])]
    public function player(string $steamId): Response
    {
        $stats = $this->steamApi->getCS2Stats($steamId);

        if (!$stats || !$stats['profile']) {
            $this->addFlash('error', 'Joueur non trouvé ou profil privé');
            return $this->redirectToRoute('app_cs2_stats');
        }

        return $this->render('cs2_stats/player.html.twig', [
            'profile' => $stats['profile'],
            'stats' => $stats['stats'],
            'playtime' => $stats['playtime'],
        ]);
    }
}
