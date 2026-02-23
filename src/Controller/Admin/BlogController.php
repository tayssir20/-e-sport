<?php

namespace App\Controller\Admin;

use App\Entity\Blog;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/blogs')]
class BlogController extends AbstractController
{
    /**
     * Display all blogs
     */
    #[Route('/', name: 'admin_blog_index', methods: ['GET'])]
    public function index(BlogRepository $blogRepository, Request $request): Response
    {
        $searchQuery = $request->query->get('q', '');

        if ($searchQuery) {
            $blogs = $blogRepository->search($searchQuery);
        } else {
            $blogs = $blogRepository->findAll();
        }

        return $this->render('blog/admin/index.html.twig', [
            'blogs' => $blogs,
            'search_query' => $searchQuery,
            'total_blogs' => count($blogs),
        ]);
    }

    /**
     * Create new blog
     */
    #[Route('/new', name: 'admin_blog_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $blog = new Blog();
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($blog);
            $entityManager->flush();

            return $this->redirectToRoute('admin_blog_index');
        }

        return $this->render('blog/admin/new.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Show blog details
     */
    #[Route('/{id}', name: 'admin_blog_show', methods: ['GET'])]
    public function show(Blog $blog): Response
    {
        return $this->render('blog/admin/show.html.twig', [
            'blog' => $blog,
        ]);
    }

    /**
     * Edit blog
     */
    #[Route('/{id}/edit', name: 'admin_blog_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Blog $blog,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_blog_index');
        }

        return $this->render('blog/admin/edit.html.twig', [
            'blog' => $blog,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Delete blog
     */
    #[Route('/{id}', name: 'admin_blog_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Blog $blog,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $blog->getId(), $request->request->get('_token'))) {
            $entityManager->remove($blog);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_blog_index');
    }

    /**
     * Export blogs to CSV
     */
    #[Route('/export/csv', name: 'admin_blog_export_csv', methods: ['GET'])]
    public function exportCsv(BlogRepository $blogRepository, Request $request): Response
    {
        $searchQuery = $request->query->get('q', '');
        if ($searchQuery) {
            $blogs = $blogRepository->search($searchQuery);
        } else {
            $blogs = $blogRepository->findAll();
        }

        $csvData = "ID,Title,Author,Category,CreatedAt\n";
        foreach ($blogs as $blog) {
            $csvData .= sprintf(
                "%d,%s,%s,%s,%s\n",
                $blog->getId(),
                str_replace(',', ' ', $blog->getTitle() ?? ''),
                method_exists($blog, 'getAuthor') ? (string) $blog->getAuthor() : '',
                $blog->getCategory() ?? '',
                $blog->getCreatedAt() ? $blog->getCreatedAt()->format('Y-m-d H:i:s') : ''
            );
        }

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="blogs-' . date('Y-m-d') . '.csv"');

        return $response;
    }
}
