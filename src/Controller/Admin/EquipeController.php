<?php

namespace App\Controller\Admin;

use App\Entity\Equipe;
use App\Form\Equipe1Type;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/equipe')]
class EquipeController extends AbstractController
{
    #[Route('/', name: 'admin_equipe_index', methods: ['GET'])]
    public function index(EquipeRepository $equipeRepository): Response
    {
        return $this->render('equipe/admin/index.html.twig', [
            'equipes' => $equipeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_equipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $equipe = new Equipe();
        $form = $this->createForm(Equipe1Type::class, $equipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('teams_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $equipe->setLogo($newFilename);
            }

            $entityManager->persist($equipe);
            $entityManager->flush();

            return $this->redirectToRoute('admin_equipe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipe/admin/new.html.twig', [
            'equipe' => $equipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_equipe_show', methods: ['GET'])]
    public function show(Equipe $equipe): Response
    {
        return $this->render('equipe/admin/show.html.twig', [
            'equipe' => $equipe,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_equipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipe $equipe, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(Equipe1Type::class, $equipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('teams_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $equipe->setLogo($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('admin_equipe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipe/admin/edit.html.twig', [
            'equipe' => $equipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_equipe_delete', methods: ['POST'])]
    public function delete(Request $request, Equipe $equipe, EntityManagerInterface $entityManager, \App\Repository\MatchGameRepository $matchGameRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$equipe->getId(), $request->request->get('_token'))) {
            foreach ($matchGameRepository->findByEquipe($equipe) as $matchGame) {
                $entityManager->remove($matchGame);
            }
            $inscriptions = $entityManager->getRepository(\App\Entity\InscriptionTournoi::class)->findBy(['equipe' => $equipe]);
            foreach ($inscriptions as $inscription) {
                $entityManager->remove($inscription);
            }
            $equipe->getTournois()->clear();
            $entityManager->flush();
            $entityManager->remove($equipe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_equipe_index', [], Response::HTTP_SEE_OTHER);
    }
}
