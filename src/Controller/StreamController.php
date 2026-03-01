<?php

namespace App\Controller;

use App\Entity\Stream;
use App\Entity\StreamReaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StreamController extends AbstractController
{
    // Page publique live
    #[Route('/live', name: 'app_stream_index')]
    public function index(EntityManagerInterface $em)
    {
        // Récupérer le stream actif
        $activeStream = $em->getRepository(Stream::class)
            ->findOneBy(['isActive' => true], ['createdAt' => 'DESC']);

        // Récupérer les autres vidéos non actives
        $videos = $em->getRepository(Stream::class)
            ->createQueryBuilder('s')
            ->where('s.isActive = false')
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('stream/index.html.twig', [
            'activeStream' => $activeStream,
            'videos' => $videos,
        ]);
    }

    // AJAX pour les réactions (boutons + commentaires)
    #[Route('/stream/react/{id}', name: 'stream_interact', methods: ['POST'])]
    public function react(Stream $stream, Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            // Vérification CSRF
            $csrfToken = $request->headers->get('X-CSRF-TOKEN');
            if (!$this->isCsrfTokenValid('stream_react', $csrfToken)) {
                return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
            }

            $data = json_decode($request->getContent(), true);
            $type = $data['type'] ?? 'comment';
            $comment = $data['comment'] ?? null;
            $username = $this->getUser()?->getUserIdentifier() ?? 'Guest';

            // Créer la réaction
            $reaction = new StreamReaction();
            $reaction->setType($type);
            $reaction->setComment($comment);
            $reaction->setUsername($username);
            $reaction->setCreatedAt(new \DateTimeImmutable());
            $reaction->setStream($stream);

            $em->persist($reaction);
            $em->flush();

            // Compter les réactions du même type
            $count = count(
                $em->getRepository(StreamReaction::class)
                    ->findBy(['stream' => $stream, 'type' => $type])
            );

            return $this->json([
                'username' => $username,
                'comment' => $comment,
                'type' => $type,
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}