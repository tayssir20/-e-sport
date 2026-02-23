<?php

namespace App\Controller;

use App\Service\TournamentAssistantService;
use App\Repository\JeuRepository;
use App\Repository\TournoiRepository;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tournament-assistant')]
class TournamentAssistantController extends AbstractController
{
    #[Route('/message', name: 'tournament_assistant_message', methods: ['POST'])]
    public function message(
        Request $request,
        TournamentAssistantService $assistant,
        SessionInterface $session,
        JeuRepository $jeuRepository,
        TournoiRepository $tournoiRepository,
        EquipeRepository $equipeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        // Récupérer le profil utilisateur si connecté
        $userProfile = null;
        if ($this->getUser()) {
            $userProfile = $this->buildUserProfile($this->getUser(), $equipeRepository, $entityManager);
        }

        // Construire le contexte complet
        $context = $this->buildContext($jeuRepository, $tournoiRepository, $userProfile);

        $history = $session->get('tournament_chat_history', []);
        $response = $assistant->chat($userMessage, $history, $context, $userProfile);

        $history[] = ['role' => 'user', 'text' => $userMessage];
        $history[] = ['role' => 'model', 'text' => $response];

        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        $session->set('tournament_chat_history', $history);

        return $this->json(['response' => $response]);
    }

    #[Route('/reset', name: 'tournament_assistant_reset', methods: ['POST'])]
    public function reset(SessionInterface $session): JsonResponse
    {
        $session->remove('tournament_chat_history');
        return $this->json(['status' => 'ok']);
    }

    private function buildUserProfile($user, EquipeRepository $equipeRepository, EntityManagerInterface $entityManager): array
    {
        $profile = [
            'username' => $user->getNom() ?? $user->getEmail(),
            'email' => $user->getEmail(),
            'teams' => [],
            'stats' => [
                'total_matches' => 0,
                'wins' => 0,
                'losses' => 0,
                'winrate' => 0,
                'favorite_game' => null
            ]
        ];

        // Récupérer les équipes de l'utilisateur
        $userTeams = $equipeRepository->findEligibleForUser($user);
        
        $totalMatches = 0;
        $totalWins = 0;
        $gameStats = [];

        foreach ($userTeams as $team) {
            $teamData = [
                'id' => $team->getId(),
                'name' => $team->getNom(),
                'role' => $team->getOwner() === $user ? 'owner' : 'member',
                'members_count' => $team->getMembers()->count(),
                'max_members' => $team->getMaxMembers(),
                'tournaments' => []
            ];

            // Statistiques des matchs de cette équipe
            $matches = $entityManager->getRepository(\App\Entity\MatchGame::class)
                ->createQueryBuilder('m')
                ->where('m.equipe1 = :team OR m.equipe2 = :team')
                ->andWhere('m.statut = :status')
                ->setParameter('team', $team)
                ->setParameter('status', 'Finished')
                ->getQuery()
                ->getResult();

            foreach ($matches as $match) {
                $totalMatches++;
                $s1 = $match->getScoreTeam1() ?? 0;
                $s2 = $match->getScoreTeam2() ?? 0;

                // Compter les victoires
                if (($match->getEquipe1() === $team && $s1 > $s2) || 
                    ($match->getEquipe2() === $team && $s2 > $s1)) {
                    $totalWins++;
                }

                // Statistiques par jeu
                $tournoi = $match->getTournoi();
                if ($tournoi && $tournoi->getJeu()) {
                    $gameName = $tournoi->getJeu()->getNom();
                    if (!isset($gameStats[$gameName])) {
                        $gameStats[$gameName] = 0;
                    }
                    $gameStats[$gameName]++;
                }
            }

            // Tournois de l'équipe
            foreach ($team->getTournois() as $tournoi) {
                $teamData['tournaments'][] = [
                    'id' => $tournoi->getId(),
                    'name' => $tournoi->getNom(),
                    'game' => $tournoi->getJeu() ? $tournoi->getJeu()->getNom() : 'N/A',
                    'status' => $tournoi->getStatut()
                ];
            }

            $profile['teams'][] = $teamData;
        }

        // Calculer les statistiques globales
        $profile['stats']['total_matches'] = $totalMatches;
        $profile['stats']['wins'] = $totalWins;
        $profile['stats']['losses'] = $totalMatches - $totalWins;
        $profile['stats']['winrate'] = $totalMatches > 0 ? round(($totalWins / $totalMatches) * 100, 1) : 0;
        
        // Jeu favori (le plus joué)
        if (!empty($gameStats)) {
            arsort($gameStats);
            $profile['stats']['favorite_game'] = array_key_first($gameStats);
        }

        return $profile;
    }

    private function buildContext(
        JeuRepository $jeuRepository,
        TournoiRepository $tournoiRepository,
        ?array $userProfile
    ): string {
        $context = "";

        // Contexte Jeux
        $jeux = $jeuRepository->findAll();
        $context .= "=== JEUX DISPONIBLES ===\n";
        foreach ($jeux as $j) {
            $nbTournois = $j->getTournois()->count();
            $context .= "- {$j->getNom()} | Genre: {$j->getGenre()} | Plateforme: {$j->getPlateforme()} | Statut: {$j->getStatut()} | Tournois actifs: {$nbTournois}\n";
        }
        $context .= "\n";

        // Contexte Tournois
        $tournois = $tournoiRepository->findAllWithEquipes();
        $context .= "=== TOURNOIS DISPONIBLES ===\n";
        foreach ($tournois as $t) {
            $jeuNom = $t->getJeu() ? $t->getJeu()->getNom() : 'N/A';
            $nbEquipes = $t->getEquipes()->count();
            $maxEquipes = $t->getMaxParticipants() ?? 'Illimité';
            $frais = $t->getFraisInscription() ? $t->getFraisInscription() . '€' : 'Gratuit';
            $dateDebut = $t->getDateDebut() ? $t->getDateDebut()->format('d/m/Y') : 'N/A';
            $dateLimite = $t->getDateInscriptionLimite() ? $t->getDateInscriptionLimite()->format('d/m/Y') : 'N/A';
            
            $context .= "- ID: {$t->getId()} | {$t->getNom()} | Jeu: {$jeuNom} | Statut: {$t->getStatut()} | Début: {$dateDebut} | Limite inscription: {$dateLimite} | Frais: {$frais} | Équipes: {$nbEquipes}/{$maxEquipes}\n";
        }
        $context .= "\n";

        // Contexte utilisateur
        if ($userProfile) {
            $context .= "=== PROFIL UTILISATEUR ===\n";
            $context .= "Nom: {$userProfile['username']}\n";
            $context .= "Équipes: " . count($userProfile['teams']) . "\n";
            $context .= "Matchs joués: {$userProfile['stats']['total_matches']}\n";
            $context .= "Victoires: {$userProfile['stats']['wins']}\n";
            $context .= "Défaites: {$userProfile['stats']['losses']}\n";
            $context .= "Winrate: {$userProfile['stats']['winrate']}%\n";
            if ($userProfile['stats']['favorite_game']) {
                $context .= "Jeu favori: {$userProfile['stats']['favorite_game']}\n";
            }
            
            if (!empty($userProfile['teams'])) {
                $context .= "\nMes équipes:\n";
                foreach ($userProfile['teams'] as $team) {
                    $context .= "  - {$team['name']} ({$team['role']}) | Membres: {$team['members_count']}/{$team['max_members']}\n";
                }
            }
        }

        return $context;
    }
}
