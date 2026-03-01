<?php

// src/Controller/ProfileController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ProfileEditType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('profile/index.html.twig', ['user' => $user]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $em->persist($user);
            $em->flush();

            // $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form->createView()
        ]);
    }
}
