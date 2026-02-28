<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private string $apiKey;
    private HttpClientInterface $client;

    public function __construct(string $apiKey, HttpClientInterface $client)
    {
        $this->apiKey = $apiKey;
        $this->client = $client;
    }

    public function chat(string $userMessage, array $history = [], string $productContext = ''): string
    {
        $messages = [];

        $messages[] = [
            'role' => 'system',
            'content' => "Tu es un assistant expert pour une boutique e-gaming. 
            Réponds en français. Réponses courtes (2-3 lignes max).
            Ne liste pas les produits sauf si demandé.
            Montre seulement nom + prix sauf demande contraire.
            Sois naturel.\n" . $productContext
        ];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'model' ? 'assistant' : 'user',
                'content' => $msg['text']
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        try {
            $response = $this->client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => $messages
                ],
                'timeout' => 30
            ]);

            $data = $response->toArray();

            return $data['choices'][0]['message']['content'] ?? 'Erreur IA';

        } catch (\Exception $e) {
            return 'Erreur : ' . $e->getMessage();
        }
    }
}