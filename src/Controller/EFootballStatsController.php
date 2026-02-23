<?php

namespace App\Controller;

use App\Service\SteamApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EFootballStatsController extends AbstractController
{
    private SteamApiService $steamApi;

    public function __construct(SteamApiService $steamApi)
    {
        $this->steamApi = $steamApi;
    }

    #[Route('/stats/efootball', name: 'app_efootball_stats', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->steamApi->isConfigured()) {
            return $this->render('game_stats/coming_soon.html.twig', [
                'game' => 'eFootball',
                'icon' => '🏆',
                'color' => '#0066cc',
                'api' => 'Steam API',
                'apiConfigured' => false,
                'apiKeyUrl' => 'https://steamcommunity.com/dev/apikey',
                'features' => [
                    'Rang/Division',
                    'Matchs joués',
                    'Points GP',
                    'Taux de victoire',
                    'Buts moyens par match',
                    'Stats générales'
                ]
            ]);
        }

        return $this->render('efootball_stats/index.html.twig');
    }

    #[Route('/stats/efootball/search', name: 'app_efootball_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $steamId = $request->request->get('steam_id');
        
        if (!$steamId) {
            $this->addFlash('error', 'Veuillez entrer un Steam ID ou vanity URL');
            return $this->redirectToRoute('app_efootball_stats');
        }

        // Si ce n'est pas un nombre, essayer de résoudre la vanity URL
        if (!is_numeric($steamId)) {
            $resolvedId = $this->steamApi->resolveVanityUrl($steamId);
            if (!$resolvedId) {
                $this->addFlash('error', 'Steam ID ou vanity URL invalide');
                return $this->redirectToRoute('app_efootball_stats');
            }
            $steamId = $resolvedId;
        }
        
        return $this->redirectToRoute('app_efootball_player', ['steamId' => $steamId]);
    }

    #[Route('/stats/efootball/player/{steamId}', name: 'app_efootball_player', methods: ['GET'])]
    public function player(string $steamId): Response
    {
        $stats = $this->steamApi->getEFootballStats($steamId);

        if (!$stats || !$stats['profile']) {
            $this->addFlash('error', 'Joueur non trouvé ou profil privé');
            return $this->redirectToRoute('app_efootball_stats');
        }

        return $this->render('efootball_stats/player.html.twig', [
            'profile' => $stats['profile'],
            'stats' => $stats['stats'],
            'playtime' => $stats['playtime'],
        ]);
    }
}
