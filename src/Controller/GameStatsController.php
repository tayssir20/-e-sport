<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur unifié pour tous les jeux
 * 
 * Note: Toutes les routes ont été déplacées vers leurs contrôleurs dédiés:
 * - Valorant → ValorantStatsController
 * - CS2 → CS2StatsController
 * - FIFA → FIFAStatsController
 * - eFootball → EFootballStatsController
 * - PUBG → PubgStatsController
 */
class GameStatsController extends AbstractController
{
    // Toutes les routes ont été déplacées vers les contrôleurs dédiés
}
