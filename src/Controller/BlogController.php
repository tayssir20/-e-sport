<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use App\Entity\Rating;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use App\Repository\RatingRepository; // Une seule fois !
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/blog')]
final class BlogController extends AbstractController
{
    #[Route(name: 'app_blog_index', methods: ['GET'])]
    public function index(BlogRepository $blogRepository): Response
    {
        return $this->render('blog/index.html.twig', [
            'blogs' => $blogRepository->findAll(),
        ]);
    }

    #[Route('/blogs', name: 'app_blog_user_index', methods: ['GET'])]
    public function indexUser(Request $request, BlogRepository $blogRepository): Response
    {
        $title = $request->query->get('title');
        $date = $request->query->get('date');

        $blogs = $blogRepository->searchByTitleAndDate($title, $date);

        return $this->render('blog/indexuser.html.twig', [
            'blogs' => $blogs,
        ]);
    }

    #[Route('/new', name: 'app_blog_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $blog = new Blog();
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($blog);
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/new.html.twig', [
            'blog' => $blog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_show', methods: ['GET'])]
    public function show(Blog $blog): Response
    {
        return $this->render('blog/show.html.twig', [
            'blog' => $blog,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_blog_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/edit.html.twig', [
            'blog' => $blog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_delete', methods: ['POST'])]
    public function delete(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$blog->getId(), $request->request->get('_token'))) {
            $entityManager->remove($blog);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_blog_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/comment', name: 'app_blog_comment', methods: ['POST'])]
    public function addComment(Request $request, int $id, BlogRepository $blogRepository, EntityManagerInterface $em): Response
    {
        $blog = $blogRepository->find($id);
        if (!$blog) {
            throw $this->createNotFoundException('Blog not found.');
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to comment.');
            return $this->redirectToRoute('app_blog_user_index');
        }

        $content = $request->request->get('content');
        if ($content) {
            $comment = new Comment();
            $comment->setUser($user);
            $comment->setBlog($blog);
            $comment->setContent($content);
            $comment->setCreatedAt(new \DateTimeImmutable());

            $em->persist($comment);
            $em->flush();
            
            $this->addFlash('success', 'Your comment has been added successfully.');
        }

        return $this->redirectToRoute('app_blog_show', ['id' => $blog->getId()]);
    }

    // ======================= ⭐ RATING SYSTEM =======================
    
    #[Route('/{id}/rate', name: 'app_blog_rate', methods: ['POST'])]
    public function rate(Request $request, Blog $blog, EntityManagerInterface $em, RatingRepository $ratingRepository): Response
    {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to rate.');
            return $this->redirectToRoute('app_blog_user_index');
        }

        // Récupérer et valider la note
        $value = (int)$request->request->get('value');
        if ($value < 1 || $value > 5) {
            $this->addFlash('error', 'Invalid rating value. Must be between 1 and 5.');
            return $this->redirectToRoute('app_blog_user_index');
        }

        try {
            // Vérifier si l'utilisateur a déjà noté ce blog
            $existingRating = $ratingRepository->findOneBy([
                'blog' => $blog,
                'user' => $user
            ]);

            if ($existingRating) {
                // Mettre à jour la note existante
                $existingRating->setValue($value);
                $existingRating->setUpdatedAt(new \DateTimeImmutable());
                $message = 'Your rating has been updated successfully!';
            } else {
                // Créer une nouvelle note
                $rating = new Rating();
                $rating->setBlog($blog);
                $rating->setUser($user);
                $rating->setValue($value);
                $em->persist($rating);
                $message = 'Your rating has been added successfully!';
            }

            $em->flush();
            $this->addFlash('success', $message);

        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while saving your rating. Please try again.');
        }

        // Rediriger vers la page principale des blogs
        return $this->redirectToRoute('app_blog_user_index');
    }

    // ======================= ⭐ API ENDPOINTS FOR AJAX =======================
    
    #[Route('/api/{id}/rate', name: 'app_blog_api_rate', methods: ['POST'])]
    public function apiRate(Request $request, Blog $blog, EntityManagerInterface $em, RatingRepository $ratingRepository): JsonResponse
    {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'You must be logged in to rate.'], 403);
        }

        // Récupérer et valider la note
        $value = (int)$request->request->get('value');
        if ($value < 1 || $value > 5) {
            return $this->json(['error' => 'Invalid rating value. Must be between 1 and 5.'], 400);
        }

        try {
            // Vérifier si l'utilisateur a déjà noté ce blog
            $existingRating = $ratingRepository->findOneBy([
                'blog' => $blog,
                'user' => $user
            ]);

            if ($existingRating) {
                // Mettre à jour la note existante
                $existingRating->setValue($value);
                $existingRating->setUpdatedAt(new \DateTimeImmutable());
                $message = 'Your rating has been updated successfully!';
            } else {
                // Créer une nouvelle note
                $rating = new Rating();
                $rating->setBlog($blog);
                $rating->setUser($user);
                $rating->setValue($value);
                $em->persist($rating);
                $message = 'Your rating has been added successfully!';
            }

            $em->flush();

            return $this->json([
                'success' => true,
                'message' => $message,
                'average' => $blog->getAverageRating(),
                'count' => $blog->getRatingCount(),
                'userRating' => $value,
                'distribution' => $blog->getRatingDistribution()
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while saving your rating.'], 500);
        }
    }

    #[Route('/api/{id}/rating-stats', name: 'app_blog_api_rating_stats', methods: ['GET'])]
    public function getRatingStats(Blog $blog): JsonResponse
    {
        return $this->json([
            'average' => $blog->getAverageRating(),
            'count' => $blog->getRatingCount(),
            'distribution' => $blog->getRatingDistribution(),
            'userRating' => $this->getUser() ? $blog->getUserRating($this->getUser()->getId()) : null
        ]);
    }

    // ======================= ⭐ TOP RATED BLOGS =======================
    
    #[Route('/top-rated', name: 'app_blog_top_rated', methods: ['GET'])]
    public function topRated(BlogRepository $blogRepository): Response
    {
        // Note: Vous devez ajouter la méthode findTopRated() dans BlogRepository
        $topBlogs = $blogRepository->findTopRated(10);
        
        return $this->render('blog/top_rated.html.twig', [
            'blogs' => $topBlogs
        ]);
    }
}