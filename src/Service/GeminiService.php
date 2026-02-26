<?php

namespace App\Service;

class GeminiService
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function chat(string $userMessage, array $history = [], string $productContext = '', string $systemPromptOverride = ''): string
    {
        $messages = [];

        if ($systemPromptOverride !== '') {
            // Contexte personnalisé (e-sport, tournoi, équipe…)
            $messages[] = ['role' => 'system', 'content' => $systemPromptOverride];
        } else {
            $messages[] = [
                'role' => 'system',
                'content' => "Tu es un assistant expert pour une boutique e-gaming. Tu réponds en français.
    Règles STRICTES :
    - Réponds toujours de façon courte (2-3 lignes maximum)
    - N'affiche JAMAIS une liste de produits sans qu'on te le demande
    - Si le client demande des produits, montre UNIQUEMENT nom + prix, rien d'autre
    - Montre description, stock, catégorie SEULEMENT si le client demande explicitement
    - Pose des questions pour mieux orienter le client
    - Sois naturel comme un vrai vendeur en magasin
    \n" . $productContext
            ];
        }

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'model' ? 'assistant' : 'user',
                'content' => $msg['text']
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $body = json_encode([
            'model' => 'llama-3.1-8b-instant',
            'messages' => $messages
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);

        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        return isset($data['error']['message']) ? $data['error']['message'] : 'Erreur: ' . $result;
    }
}