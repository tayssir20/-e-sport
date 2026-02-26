<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use App\Entity\Rating;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use App\Repository\RatingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Knp\Component\Pager\PaginatorInterface; // 👈 AJOUT

#[Route('/blog')]
final class BlogController extends AbstractController
{
    private $httpClient;
    
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route(name: 'app_blog_index', methods: ['GET'])]
    public function index(BlogRepository $blogRepository, Request $request, PaginatorInterface $paginator): Response // 👈 MODIFIÉ
    {
        $query = $blogRepository->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery();
        
        $blogs = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // Items per page for admin
        );

        return $this->render('blog/index.html.twig', [
            'blogs' => $blogs,
        ]);
    }

    #[Route('/blogs', name: 'app_blog_user_index', methods: ['GET'])]
    public function indexUser(Request $request, BlogRepository $blogRepository, PaginatorInterface $paginator): Response // 👈 MODIFIÉ
    {
        $title = $request->query->get('title');
        $date = $request->query->get('date');

        // Créer une requête pour la pagination
        $queryBuilder = $blogRepository->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC');
        
        if ($title) {
            $queryBuilder->andWhere('b.title LIKE :title')
                ->setParameter('title', '%' . $title . '%');
        }
        
        if ($date) {
            $queryBuilder->andWhere('b.createdAt LIKE :date')
                ->setParameter('date', $date . '%');
        }
        
        $query = $queryBuilder->getQuery();

        $blogs = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5 // Items per page for users
        );

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

    // ======================= 📝 COMMENTAIRES AVEC RECAPTCHA =======================
    
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

        // 🔐 VALIDATION RECAPTCHA
        $recaptchaResponse = $request->request->get('g-recaptcha-response');
        
        if (!$recaptchaResponse) {
            $this->addFlash('error', 'Please complete the reCAPTCHA verification.');
            return $this->redirectToRoute('app_blog_show', ['id' => $blog->getId()]);
        }

        // Vérifier le token reCAPTCHA auprès de Google
        $secretKey = $_ENV['EWZ_RECAPTCHA_SECRET'] ?? '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
        
        try {
            $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $secretKey,
                    'response' => $recaptchaResponse,
                    'remoteip' => $request->getClientIp()
                ]
            ]);
            
            $result = $response->toArray();
            
            if (!isset($result['success']) || $result['success'] !== true) {
                $errorCodes = isset($result['error-codes']) ? implode(', ', $result['error-codes']) : 'Unknown error';
                $this->addFlash('error', 'reCAPTCHA verification failed. Please try again. (Erreur: ' . $errorCodes . ')');
                return $this->redirectToRoute('app_blog_show', ['id' => $blog->getId()]);
            }
            
            if (isset($result['score']) && $result['score'] < 0.5) {
                $this->addFlash('error', 'Suspicious activity detected. Your comment has been rejected.');
                return $this->redirectToRoute('app_blog_show', ['id' => $blog->getId()]);
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error validating reCAPTCHA. Please try again.');
            return $this->redirectToRoute('app_blog_show', ['id' => $blog->getId()]);
        }

        // Si reCAPTCHA est valide, traiter le commentaire
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
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to rate.');
            return $this->redirectToRoute('app_blog_user_index');
        }

        $value = (int)$request->request->get('value');
        if ($value < 1 || $value > 5) {
            $this->addFlash('error', 'Invalid rating value. Must be between 1 and 5.');
            return $this->redirectToRoute('app_blog_user_index');
        }

        try {
            $existingRating = $ratingRepository->findOneBy([
                'blog' => $blog,
                'user' => $user
            ]);

            if ($existingRating) {
                $existingRating->setValue($value);
                $existingRating->setUpdatedAt(new \DateTimeImmutable());
                $message = 'Your rating has been updated successfully!';
            } else {
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

        return $this->redirectToRoute('app_blog_user_index');
    }

    // ======================= ⭐ API ENDPOINTS FOR AJAX =======================
    
    #[Route('/api/{id}/rate', name: 'app_blog_api_rate', methods: ['POST'])]
    public function apiRate(Request $request, Blog $blog, EntityManagerInterface $em, RatingRepository $ratingRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'You must be logged in to rate.'], 403);
        }

        $value = (int)$request->request->get('value');
        if ($value < 1 || $value > 5) {
            return $this->json(['error' => 'Invalid rating value. Must be between 1 and 5.'], 400);
        }

        try {
            $existingRating = $ratingRepository->findOneBy([
                'blog' => $blog,
                'user' => $user
            ]);

            if ($existingRating) {
                $existingRating->setValue($value);
                $existingRating->setUpdatedAt(new \DateTimeImmutable());
                $message = 'Your rating has been updated successfully!';
            } else {
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
        $topBlogs = $blogRepository->findTopRated(10);
        
        return $this->render('blog/top_rated.html.twig', [
            'blogs' => $topBlogs
        ]);
    }
}