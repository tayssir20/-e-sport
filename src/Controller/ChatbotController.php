<?php

namespace App\Controller;

use App\Service\GeminiService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ChatbotController extends AbstractController
{
    #[Route('/chatbot/message', name: 'chatbot_message', methods: ['POST'])]
    public function message(
        Request $request,
        GeminiService $gemini,
        SessionInterface $session,
        ProductRepository $productRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        // Récupérer tous les produits
        $products = $productRepository->findAll();

        // Construire le contexte produits pour l'IA
        $productContext = "Voici les produits disponibles dans notre boutique e-gaming :\n";
        foreach ($products as $p) {
            $productContext .= "- {$p->getName()} | Prix: {$p->getPrice()}€ | Catégorie: {$p->getCategory()->getName()} | Stock: {$p->getStock()} | Description: {$p->getDescription()}\n";
        }

        $history = $session->get('chat_history', []);
        $response = $gemini->chat($userMessage, $history, $productContext);

        $history[] = ['role' => 'user', 'text' => $userMessage];
        $history[] = ['role' => 'model', 'text' => $response];

        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        $session->set('chat_history', $history);

        return $this->json(['response' => $response]);
    }

    #[Route('/chatbot/reset', name: 'chatbot_reset', methods: ['POST'])]
    public function reset(SessionInterface $session): JsonResponse
    {
        $session->remove('chat_history');
        return $this->json(['status' => 'ok']);
    }
}