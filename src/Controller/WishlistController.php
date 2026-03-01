<?php

namespace App\Controller;

use App\Entity\Wishlist;
use Symfony\Component\HttpFoundation\Request;  // ✅ corrigé
use App\Repository\WishlistRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/wishlist', name: 'app_wishlist_')]
class WishlistController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(WishlistRepository $wishlistRepository): Response
    {
       /** @var \App\Entity\User $user */
$user = $this->getUser();
$items = $wishlistRepository->findByUser($user);
        return $this->render('wishlist/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/toggle/{id}', name: 'toggle')]  // ✅ corrigé
    public function toggle(
        int $id,
        Request $request,
        ProductRepository $productRepository,
        WishlistRepository $wishlistRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException();
        }

  /** @var \App\Entity\User $user */
$user = $this->getUser();
$existing = $wishlistRepository->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
            $this->addFlash('info', '💔 Retiré de votre wishlist');
        } else {
            $wishlist = new Wishlist();
            $wishlist->setUser($user);
            $wishlist->setProduct($product);
            $em->persist($wishlist);
            $em->flush();
            $this->addFlash('success', '❤️ Ajouté à votre wishlist !');
        }

        $referer = $request->headers->get('referer');
        return $referer
            ? $this->redirect($referer)
            : $this->redirectToRoute('app_product_index2');
    }

    #[Route('/remove/{id}', name: 'remove')]
    public function remove(
        int $id,
        WishlistRepository $wishlistRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $item = $wishlistRepository->find($id);

       /** @var \App\Entity\User $user */
$user = $this->getUser();
if ($item && $item->getUser() === $user) {
            $em->remove($item);
            $em->flush();
            $this->addFlash('info', '💔 Retiré de votre wishlist');
        }

        return $this->redirectToRoute('app_wishlist_index');
    }
}