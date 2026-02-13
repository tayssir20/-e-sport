<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Entity\User;
use App\Entity\Tournoi;
use App\Entity\MatchGame;
use App\Entity\InscriptionTournoi;
use App\Form\Equipe1Type;
use App\Repository\MatchGameRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/equipe')]
#[IsGranted('ROLE_USER')]
final class EquipeController extends AbstractController
{
    #[Route(name: 'app_equipe_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('equipe/index.html.twig');
    }

    #[Route('/equipes', name: 'app_equipe_list', methods: ['GET'])]
    public function get(EntityManagerInterface $entityManager): Response
    {
        $equipes = $entityManager->getRepository(Equipe::class)->findAll();

        return $this->render('equipe/afficher.html.twig', [
            'equipes' => $equipes,
        ]);
    }

    #[Route('/new', name: 'app_equipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $equipe = new Equipe();
        $user = $this->getUser();
        if ($user) {
            $equipe->setOwner($user);
            $equipe->addMember($user);
        }

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
                        $this->getParameter('teams_directory'), // Ensure this parameter exists in services.yaml or similar
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                $equipe->setLogo($newFilename);
            }

            $entityManager->persist($equipe);
            $entityManager->flush();

            $returnToTournoi = $request->query->getInt('return_to_tournoi', 0) ?: $request->request->getInt('return_to_tournoi', 0);
            if ($returnToTournoi > 0) {
                return $this->redirectToRoute('app_tournoi_register', ['id' => $returnToTournoi]);
            }

            return $this->redirectToRoute('app_equipe_dashboard', ['id' => $equipe->getId()]);
        }

        $returnToTournoi = $request->query->getInt('return_to_tournoi', 0);

        return $this->render('equipe/new.html.twig', [
            'equipe' => $equipe,
            'form' => $form,
            'return_to_tournoi' => $returnToTournoi ?: null,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_equipe_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }

        return $this->render('equipe/show.html.twig', [
            'equipe' => $equipe,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_equipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
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
                     // ... handle exception
                 }
                 $equipe->setLogo($newFilename);
             }

            $entityManager->flush();

            return $this->redirectToRoute('app_equipe_dashboard', ['id' => $equipe->getId()]);
        }

        return $this->render('equipe/edit.html.twig', [
            'equipe' => $equipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_equipe_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager, MatchGameRepository $matchGameRepository): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
        if ($this->isCsrfTokenValid('delete'.$equipe->getId(), $request->request->get('_token'))) {
            foreach ($matchGameRepository->findByEquipe($equipe) as $matchGame) {
                $entityManager->remove($matchGame);
            }
            $inscriptions = $entityManager->getRepository(InscriptionTournoi::class)->findBy(['equipe' => $equipe]);
            foreach ($inscriptions as $inscription) {
                $entityManager->remove($inscription);
            }
            $equipe->getTournois()->clear();
            $entityManager->flush();
            $entityManager->remove($equipe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_equipe_index');
    }

    #[Route('/{id<\d+>}/membres', name: 'app_equipe_membres', methods: ['GET','POST'])]
    public function invite(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
    
        // Tous les users sauf admins et le owner de cette équipe
        $allUsers = $entityManager->getRepository(User::class)->findAll();
        $users = array_filter($allUsers, function($u) use ($equipe) {
            $isAdmin = in_array('ROLE_ADMIN', $u->getRoles());
            $isOwner = ($u === $equipe->getOwner());
            return !$isAdmin && !$isOwner;
        });
    
        // IDs des membres déjà dans l'équipe
        $teamUsers = [];
        foreach ($equipe->getMembers() as $member) {
            $teamUsers[] = $member->getId();
        }
    
        // Ajout d'un joueur
        if ($request->isMethod('POST')) {
            $userId = $request->request->get('user_id');
    
            if ($userId) {
                $user = $entityManager->getRepository(User::class)->find($userId);
    
                if ($user && !in_array($user->getId(), $teamUsers)) {
                    $equipe->addMember($user);
                    $entityManager->flush();
                }
            }
    
            return $this->redirectToRoute('app_equipe_membres', ['id' => $id]);
        }
    
        return $this->render('equipe/invite.html.twig', [
            'equipe'    => $equipe,
            'users'     => $users,
            'teamUsers' => $teamUsers,
            'isOwner'   => $this->getUser() === $equipe->getOwner(),
        ]);
    }

    #[Route('/{id<\d+>}/remove-member', name: 'app_equipe_remove_member', methods: ['POST'])]
    public function removeMember(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }

        // Only owner can remove members
        if ($this->getUser() !== $equipe->getOwner()) {
            throw $this->createAccessDeniedException('You are not the owner of this team.');
        }

        $userId = $request->request->get('user_id');
        if ($userId) {
            $userToRemove = $entityManager->getRepository(User::class)->find($userId);
            if ($userToRemove && $equipe->getMembers()->contains($userToRemove)) {
                // Cannot remove owner
                if ($userToRemove === $equipe->getOwner()) {
                } else {
                    $equipe->removeMember($userToRemove);
                    $entityManager->flush();
                }
            }
        }

        return $this->redirectToRoute('app_equipe_membres', ['id' => $id]);
    }

    #[Route('/my-teams', name: 'app_my_teams', methods: ['GET'])]
    public function myTeams(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $ownedTeams = $entityManager->getRepository(Equipe::class)->findBy(['owner' => $user]);

        $qb = $entityManager->createQueryBuilder();
        $qb->select('e')
            ->from(Equipe::class, 'e')
            ->join('e.members', 'm')
            ->where('m = :user')
            ->andWhere('e.owner != :user')
            ->setParameter('user', $user);
        $joinedTeams = $qb->getQuery()->getResult();

        // Calculate Global Win Rate
        $allTeams = array_unique(array_merge($ownedTeams, $joinedTeams), SORT_REGULAR);
        $teamIds = array_map(fn($t) => $t->getId(), $allTeams);

        $totalWins = 0;
        $totalEndedMatches = 0;

        if (!empty($teamIds)) {
            $matches = $entityManager->getRepository(MatchGame::class)->createQueryBuilder('m')
                ->where('m.equipe1 IN (:teamIds) OR m.equipe2 IN (:teamIds)')
                ->andWhere('m.statut = :status')
                ->setParameter('teamIds', $teamIds)
                ->setParameter('status', 'TERMINE')
                ->getQuery()
                ->getResult();

            foreach ($matches as $match) {
                $totalEndedMatches++;
                $e1 = $match->getEquipe1();
                $e2 = $match->getEquipe2();
                $s1 = $match->getScoreTeam1();
                $s2 = $match->getScoreTeam2();

                // Check if one of user's teams won
                if (in_array($e1->getId(), $teamIds) && $s1 > $s2) {
                    $totalWins++;
                } elseif (in_array($e2->getId(), $teamIds) && $s2 > $s1) {
                    $totalWins++;
                }
            }
        }

        $globalWinRate = $totalEndedMatches > 0 ? round(($totalWins / $totalEndedMatches) * 100) : 0;
        $total = count($ownedTeams) + count($joinedTeams);

        return $this->render('equipe/my-teams.html.twig', [
            'ownedTeams' => $ownedTeams,
            'joinedTeams' => $joinedTeams,
            'totalTeams' => $total,
            'globalWinRate' => $globalWinRate,
        ]);
    }
    
    #[Route('/{id<\d+>}/dashboard', name: 'app_equipe_dashboard', methods: ['GET'])]
    public function dashboard(int $id, EntityManagerInterface $entityManager): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);

        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }

        $user = $this->getUser();
        
        // Membership Logic for Template
        $membership = ['role' => 'VISITOR'];
        if ($user) {
            if ($equipe->getOwner() === $user) {
                $membership = ['role' => 'LEADER'];
            } elseif ($equipe->getMembers()->contains($user)) {
                $membership = ['role' => 'MEMBER'];
            }
        }
        
        // --- DYNAMIC STATS ---
        
        // 1. Matches Played (only 'TERMINE')
        // Actually, dashboard might want ALL matches involving this team, or just ended ones?
        // Usually, dashboard shows total matches played (ended).
        
        $matches = $entityManager->getRepository(MatchGame::class)->createQueryBuilder('m')
            ->where('m.equipe1 = :team OR m.equipe2 = :team')
            ->andWhere('m.statut = :status') // Count only finished matches for stats
            ->setParameter('team', $equipe)
            ->setParameter('status', 'TERMINE')
            ->getQuery()
            ->getResult();
            
        $totalMatches = count($matches);
        $wins = 0;
        
        foreach ($matches as $match) {
            if ($match->getEquipe1() === $equipe && $match->getScoreTeam1() > $match->getScoreTeam2()) {
                $wins++;
            } elseif ($match->getEquipe2() === $equipe && $match->getScoreTeam2() > $match->getScoreTeam1()) {
                $wins++;
            }
        }
        
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100) : 0;

        return $this->render('equipe/dashboard.html.twig', [
            'team' => $equipe,
            'membership' => (object)$membership,
            'totalMatches' => $totalMatches,
            'wins' => $wins,
            'winRate' => $winRate
        ]);
    }

    #[Route('/{id<\d+>}/join', name: 'app_equipe_join', methods: ['GET', 'POST'])]
    public function join(int $id, EntityManagerInterface $entityManager): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($equipe->getMembers()->contains($user)) {
            return $this->redirectToRoute('app_equipe_dashboard', ['id' => $equipe->getId()]);
        }

        if ($equipe->getMembers()->count() >= $equipe->getMaxMembers()) {
             return $this->redirectToRoute('app_equipe_dashboard', ['id' => $equipe->getId()]);
        }
        
        $equipe->addMember($user);
        $entityManager->flush();
        
        
        return $this->redirectToRoute('app_equipe_dashboard', ['id' => $equipe->getId()]);
    }
    
    #[Route('/{id<\d+>}/leave', name: 'app_equipe_leave', methods: ['POST'])]
    public function leave(int $id, EntityManagerInterface $entityManager): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
        $user = $this->getUser();
        if ($equipe->getMembers()->contains($user)) {
            $equipe->removeMember($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_equipe_index');
    }

    #[Route('/not-member', name: 'app_equipe_not_member', methods: ['GET'])]
    public function notMember(EntityManagerInterface $entityManager): Response 
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_equipe_index');
        }

        $equipes = $entityManager->getRepository(Equipe::class)->findAll();
        $userTeamIds = [];
        foreach ($equipes as $team) {
            if ($team->getOwner() === $user || $team->getMembers()->contains($user)) {
                $userTeamIds[] = $team->getId();
            }
        }
        
        return $this->render('equipe/afficher.html.twig', [
            'equipes' => $equipes,
            'user_team_ids' => $userTeamIds,
        ]);
    }
}
