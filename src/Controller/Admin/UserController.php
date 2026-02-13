<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
// #[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    /**
     * Display all users
     */
    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $searchQuery = $request->query->get('q', '');
        
        if ($searchQuery) {
            $users = $userRepository->search($searchQuery);
        } else {
            $users = $userRepository->findAll();
        }


        return $this->render('user/admin/index.html.twig', [
            'users' => $users,
            'search_query' => $searchQuery,
            'total_users' => count($users),
        ]);
    }

    /**
     * Create new user
     */
    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the plain password
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/admin/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'page_title' => 'Create New User',
        ]);
    }

    /**
     * Display user details
     */
    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/admin/show.html.twig', [
            'user' => $user,
            'page_title' => 'User Details',
        ]);
    }

    /**
     * Edit existing user
     */
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update password if provided
            if ($form->has('plainPassword') && $form->get('plainPassword')->getData()) {
                $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
    }

            $entityManager->flush();

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/admin/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'page_title' => 'Edit User',
        ]);
    }


    /**
     * Delete user
     */
    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();
        
        // Prevent self-deletion
        // if ($user->getId() === $currentUser->getId()) {
        //     return $this->redirectToRoute('admin_user_index');
        // }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $userEmail = $user->getEmail();
            $entityManager->remove($user);
            $entityManager->flush();
        } else {
            // invalid security token
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Toggle user status (AJAX)
     */
    #[Route('/{id}/toggle-status', name: 'admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        // For this example, we'll toggle a hypothetical 'isActive' property
        // You would need to add this property to your User entity
        // $user->setIsActive(!$user->isActive());
        // $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'User status updated',
            'is_active' => true, // Replace with actual value
        ]);
    }

    /**
     * Export users to CSV
     */
    #[Route('/export/csv', name: 'admin_user_export_csv', methods: ['GET'])]
    public function exportCsv(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAllOrdered();
        
        $csvData = "ID,Email,Name,Roles,Created\n";
        foreach ($users as $user) {
            $csvData .= sprintf(
                "%d,%s,%s,%s,%s\n",
                $user->getId(),
                $user->getEmail(),
                $user->getNom(),
                implode('|', $user->getRoles()),
                date('Y-m-d H:i:s')
            );
        }

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="users-' . date('Y-m-d') . '.csv"');

        return $response;
    }
}