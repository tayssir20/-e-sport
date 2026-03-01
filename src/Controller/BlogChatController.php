<?php
// src/Controller/BlogChatController.php
// src/Controller/BlogChatController.php
namespace App\Controller;

use App\Service\GroqService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class BlogChatController extends AbstractController
{
    #[Route('/blog/chat', name: 'blog_chat', methods: ['POST'])]
    public function chat(Request $request, GroqService $groq): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = trim($data['message'] ?? '');

        if (!$userMessage) {
            return $this->json(['error' => 'Empty message'], 400);
        }

        $reply = $groq->chatWithBlogContext($userMessage);

        return $this->json(['reply' => $reply]);
    }
}
