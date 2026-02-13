<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function indexUser(BlogRepository $blogRepository): Response
    {
        $blogs = $blogRepository->findAll();
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
        $blog = $blogRepository->find($id);          // ⚡ Fetch blog manually
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
        }

        return $this->redirectToRoute('app_blog_user_index');
    }
}
