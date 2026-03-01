<?php
// src/Controller/ProductRatingController.php

namespace App\Controller;

use App\Entity\ProductRating;
use App\Repository\ProductRatingRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/rating', name: 'app_rating_')]
class ProductRatingController extends AbstractController
{
    #[Route('/submit/{id}', name: 'submit', methods: ['POST'])]
    public function submit(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        ProductRatingRepository $ratingRepository,
        EntityManagerInterface $em
    ): RedirectResponse {

        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException();
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $stars = (int) $request->request->get('stars');
        $comment = trim($request->request->get('comment', ''));

        if ($stars < 1 || $stars > 5) {
            $this->addFlash('error', 'Note invalide.');
            return $this->redirectToRoute('app_product_show', ['id' => $id]);
        }

        // Vérifier si l'user a déjà noté
        $existing = $ratingRepository->findUserRating($user, $product);

        if ($existing) {
            // Mettre à jour
            $existing->setStars($stars);
            $existing->setComment($comment ?: null);
            $this->addFlash('success', '⭐ Note mise à jour !');
        } else {
            // Nouvelle note
            $rating = new ProductRating();
            $rating->setUser($user);
            $rating->setProduct($product);
            $rating->setStars($stars);
            $rating->setComment($comment ?: null);
            $em->persist($rating);
            $this->addFlash('success', '⭐ Merci pour votre note !');
        }

        $em->flush();

        return $this->redirectToRoute('app_product_show', ['id' => $id]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        int $id,
        ProductRatingRepository $ratingRepository,
        EntityManagerInterface $em
    ): RedirectResponse {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $rating = $ratingRepository->find($id);

        if ($rating && $rating->getUser() === $user) {
            $productId = $rating->getProduct()->getId();
            $em->remove($rating);
            $em->flush();
            $this->addFlash('info', 'Note supprimée.');
            return $this->redirectToRoute('app_product_show', ['id' => $productId]);
        }

        throw $this->createAccessDeniedException();
    }
}