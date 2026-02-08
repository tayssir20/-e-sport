<?php

namespace App\Controller;

use App\Entity\Tournoi;
use App\Form\TournoiType;
use App\Repository\TournoiRepository;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tournoi')]
final class TournoiController extends AbstractController
{
    #[Route(name: 'app_tournoi_index', methods: ['GET'])]
    public function index(TournoiRepository $tournoiRepository): Response
    {
        return $this->render('tournoi/index.html.twig', [
            'tournois' => $tournoiRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_tournoi_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tournoi = new Tournoi();
        $form = $this->createForm(TournoiType::class, $tournoi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tournoi);
            $entityManager->flush();

            return $this->redirectToRoute('app_tournoi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournoi/new.html.twig', [
            'tournoi' => $tournoi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tournoi_show', methods: ['GET'])]
    public function show(int $id, TournoiRepository $tournoiRepository): Response
    {
        $tournoi = $tournoiRepository->findOneWithJeu($id);
        
        if (!$tournoi) {
            throw $this->createNotFoundException('Tournament not found');
        }

        return $this->render('tournoi/show.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }

    #[Route('/{id}/register', name: 'app_tournoi_register', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function register(int $id, TournoiRepository $tournoiRepository, EquipeRepository $equipeRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $tournoi = $tournoiRepository->find($id);
        
        if (!$tournoi) {
            throw $this->createNotFoundException('Tournament not found');
        }

        $user = $this->getUser();
        $equipes = $equipeRepository->findBy(['owner' => $user]);

        if (empty($equipes)) {
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        if ($request->isMethod('POST')) {
            $equipeId = $request->get('equipe_id');
            $token = $request->get('token');

            // Validate CSRF token
            if (!$this->isCsrfTokenValid('register_tournament', $token)) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
            }

            if (!$equipeId) {
                $this->addFlash('error', 'Veuillez sélectionner une équipe.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
            }

            $equipe = $equipeRepository->find($equipeId);

            if (!$equipe || $equipe->getOwner() !== $user) {
                $this->addFlash('error', 'Équipe non valide.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
            }

            if ($tournoi->getEquipes()->contains($equipe)) {
                $this->addFlash('warning', 'Votre équipe est déjà inscrite à ce tournoi.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
            }

            $tournoi->addEquipe($equipe);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription au tournoi effectuée avec succès!');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        return $this->render('tournoi/register.html.twig', [
            'tournoi' => $tournoi,
            'equipes' => $equipes,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tournoi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournoi $tournoi, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TournoiType::class, $tournoi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_tournoi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournoi/edit.html.twig', [
            'tournoi' => $tournoi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tournoi_delete', methods: ['POST'])]
    public function delete(Request $request, Tournoi $tournoi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tournoi->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tournoi);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tournoi_index', [], Response::HTTP_SEE_OTHER);
    }
}
