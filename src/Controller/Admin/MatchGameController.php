<?php

namespace App\Controller\Admin;

use App\Entity\MatchGame;
use App\Form\MatchGame1Type;
use App\Repository\MatchGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/match-games')]
// #[IsGranted('ROLE_ADMIN')]
class MatchGameController extends AbstractController
{
    /**
     * Display all match games
     */
    #[Route('/', name: 'admin_match_game_index', methods: ['GET'])]
    public function index(MatchGameRepository $matchGameRepository, Request $request): Response
    {
        $searchQuery = $request->query->get('q', '');
        
        if ($searchQuery) {
            $match_games = $matchGameRepository->search($searchQuery);
        } else {
            $match_games = $matchGameRepository->findAll();
        }

        return $this->render('match_game/admin/index.html.twig', [
            'match_games' => $match_games,
            'search_query' => $searchQuery,
            'total_matches' => count($match_games),
        ]);
    }

    /**
     * Create new match game
     */
    #[Route('/new', name: 'admin_match_game_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $matchGame = new MatchGame();
        $form = $this->createForm(MatchGame1Type::class, $matchGame);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$matchGame->getEquipe1() || !$matchGame->getEquipe2()) {
                $this->addFlash('error', 'Veuillez sélectionner les deux équipes.');
            } elseif (!$matchGame->getTournoi()) {
                $this->addFlash('error', 'Le match doit appartenir à un tournoi.');
            } else {
                $entityManager->persist($matchGame);
                $entityManager->flush();
                return $this->redirectToRoute('admin_match_game_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('match_game/admin/new.html.twig', [
            'match_game' => $matchGame,
            'form' => $form->createView(),
            'page_title' => 'Create New Match',
        ]);
    }

    /**
     * Display match game details
     */
    #[Route('/{id}', name: 'admin_match_game_show', methods: ['GET'])]
    public function show(MatchGame $matchGame): Response
    {
        return $this->render('match_game/admin/show.html.twig', [
            'match_game' => $matchGame,
            'page_title' => 'Match Details',
        ]);
    }

    /**
     * Edit existing match game
     */
    #[Route('/{id}/edit', name: 'admin_match_game_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        MatchGame $matchGame,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(MatchGame1Type::class, $matchGame);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$matchGame->getEquipe1() || !$matchGame->getEquipe2()) {
                $this->addFlash('error', 'Veuillez sélectionner les deux équipes.');
            } elseif (!$matchGame->getTournoi()) {
                $this->addFlash('error', 'Le match doit appartenir à un tournoi.');
            } else {
                $entityManager->flush();
                return $this->redirectToRoute('admin_match_game_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('match_game/admin/edit.html.twig', [
            'match_game' => $matchGame,
            'form' => $form->createView(),
            'page_title' => 'Edit Match',
        ]);
    }

    /**
     * Delete match game
     */
    #[Route('/{id}', name: 'admin_match_game_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        MatchGame $matchGame,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $matchGame->getId(), $request->request->get('_token'))) {
            $matchId = $matchGame->getId();
            $entityManager->remove($matchGame);
            $entityManager->flush();

            // deletion successful
        } else {
            // invalid security token
        }

        return $this->redirectToRoute('admin_match_game_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Export match games to CSV
     */
    #[Route('/export/csv', name: 'admin_match_game_export_csv', methods: ['GET'])]
    public function exportCsv(MatchGameRepository $matchGameRepository): Response
    {
        $matchGames = $matchGameRepository->findAll();
        
        $csvData = "ID,Tournament,Team 1,Team 2,Score,Date,Status\n";
        foreach ($matchGames as $match) {
            $csvData .= sprintf(
                "%d,%s,%s,%s,%s-%s,%s,%s\n",
                $match->getId(),
                $match->getTournoi()->getNom(),
                $match->getEquipe1()->getNom(),
                $match->getEquipe2()->getNom(),
                $match->getScoreTeam1() ?? '?',
                $match->getScoreTeam2() ?? '?',
                $match->getDateMatch()->format('Y-m-d H:i'),
                $match->getStatut()
            );
        }

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="match-games-' . date('Y-m-d') . '.csv"');

        return $response;
    }
}
