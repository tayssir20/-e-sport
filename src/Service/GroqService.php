<?php
// src/Service/GroqService.php
namespace App\Service;

use App\Repository\BlogRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqService
{
    public function __construct(
        private HttpClientInterface $client,
        private BlogRepository $blogRepository,
        private string $groqApiKey,
        private string $groqModel = 'llama-3.1-8b-instant'
    ) {}

    public function chatWithBlogContext(string $userMessage): string
    {
        // Fetch all blogs and build context from your actual entity
        $blogs = $this->blogRepository->findAll();

        $context = implode("\n\n", array_map(function ($blog) {
            return sprintf(
                "Title: %s\nCategory: %s\nDate: %s\nAverage Rating: %s/5 (%d ratings)\nContent: %s",
                $blog->getTitle(),
                $blog->getCategory() ?? 'Uncategorized',
                $blog->getCreatedAt()->format('Y-m-d'),
                $blog->getAverageRating(),
                $blog->getRatingCount(),
                $blog->getContent()
            );
        }, $blogs));

        $systemPrompt = <<<PROMPT
        You are a smart assistant for a blog website. 
        You help users find and learn about blog posts.
        Here are all the available blog posts:

        $context

        Answer the user's questions based on these posts.
        If they ask to search or find a post, suggest the most relevant one.
        Be concise, friendly, and helpful.
        PROMPT;

        return $this->call($userMessage, $systemPrompt);
    }

    private function call(string $userMessage, string $systemPrompt): string
    {
        $response = $this->client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->groqApiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'    => $this->groqModel,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userMessage],
                ],
            ],
        ]);

        $data = $response->toArray();
        return $data['choices'][0]['message']['content'] ?? 'No response.';
    }
}