<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatsHubController extends AbstractController
{
    #[Route('/stats', name: 'app_stats_hub')]
    public function index(): Response
    {
        return $this->render('stats/index.html.twig');
    }

    #[Route('/stats/search', name: 'app_stats_search_redirect', methods: ['POST', 'GET'])]
    public function searchRedirect(): Response
    {
        $this->addFlash('info', 'Veuillez sélectionner un jeu pour rechercher des statistiques.');
        return $this->redirectToRoute('app_stats_hub');
    }
}
