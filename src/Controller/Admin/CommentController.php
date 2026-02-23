<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/comments')]
class CommentController extends AbstractController
{
    #[Route('/', name: 'admin_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository, Request $request): Response
    {
        $searchQuery = $request->query->get('q', '');

        if ($searchQuery) {
            $comments = $commentRepository->search($searchQuery);
        } else {
            $comments = $commentRepository->findBy([], ['createdAt' => 'DESC']);
        }

        $recent = $commentRepository->findRecentComments(5);

        return $this->render('comment/admin/index.html.twig', [
            'comments' => $comments,
            'search_query' => $searchQuery,
            'total_comments' => count($comments),
            'recent_comments' => $recent,
        ]);
    }

    #[Route('/new', name: 'admin_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $comment->getCreatedAt()) {
                $comment->setCreatedAt(new \DateTimeImmutable());
            }
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('admin_comment_index');
        }

        return $this->render('comment/admin/new.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/admin/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_comment_index');
        }

        return $this->render('comment/admin/edit.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
        } else {
            // invalid CSRF token
        }

        return $this->redirectToRoute('admin_comment_index');
    }
    
    /**
     * Export comments to CSV
     */
    #[Route('/export/csv', name: 'admin_comment_export_csv', methods: ['GET'])]
    public function exportCsv(CommentRepository $commentRepository, Request $request): Response
    {
        $searchQuery = $request->query->get('q', '');
        if ($searchQuery) {
            $comments = $commentRepository->search($searchQuery);
        } else {
            $comments = $commentRepository->findBy([], ['createdAt' => 'DESC']);
        }

        $csvData = "ID,Author,Blog,Content,CreatedAt\n";
        foreach ($comments as $c) {
            $csvData .= sprintf(
                "%d,%s,%s,%s,%s\n",
                $c->getId(),
                $c->getUser() ? str_replace(',', ' ', $c->getUser()->getNom() ?? $c->getUser()->getEmail()) : '',
                $c->getBlog() ? str_replace(',', ' ', $c->getBlog()->getTitre() ?? $c->getBlog()->getNom()) : '',
                str_replace(["\n", "\r", ","], ' ', $c->getContent() ?? ''),
                $c->getCreatedAt() ? $c->getCreatedAt()->format('Y-m-d H:i:s') : ''
            );
        }

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="comments-' . date('Y-m-d') . '.csv"');

        return $response;
    }
}

