<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categories')]
class CategoryController extends AbstractController
{
    /**
     * Display all categories
     */
    #[Route('/', name: 'admin_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository, Request $request): Response
    {
        $searchQuery = $request->query->get('q', '');

        if ($searchQuery) {
            $categories = $categoryRepository->search($searchQuery);
        } else {
            $categories = $categoryRepository->findAll();
        }

        return $this->render('category/admin/index.html.twig', [
            'categories' => $categories,
            'search_query' => $searchQuery,
            'total_categories' => count($categories),
        ]);
    }

    #[Route('/export/csv', name: 'admin_category_export_csv', methods: ['GET'])]
    public function exportCsv(CategoryRepository $categoryRepository, Request $request): Response
    {
        $searchQuery = $request->query->get('q', '');
        if ($searchQuery) {
            $categories = $categoryRepository->search($searchQuery);
        } else {
            $categories = $categoryRepository->findAllOrdered();
        }

        $csvData = "ID,Name,Description,CreatedAt\n";
        foreach ($categories as $c) {
            $csvData .= sprintf("%d,%s,%s,%s\n",
                $c->getId(),
                str_replace(',', ' ', $c->getName() ?? ''),
                str_replace(',', ' ', $c->getDescription() ?? ''),
                $c->getCreatedAt() ? $c->getCreatedAt()->format('Y-m-d H:i:s') : ''
            );
        }

        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="categories-' . date('Y-m-d') . '.csv"');

        return $response;
    }

    /**
     * Create new category
     */
    #[Route('/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('category/admin/new.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Show category details
     */
    #[Route('/{id}', name: 'admin_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/admin/show.html.twig', [
            'category' => $category,
        ]);
    }

    /**
     * Edit category
     */
    #[Route('/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('category/admin/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Delete category
     */
    #[Route('/{id}', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_category_index');
    }
}
