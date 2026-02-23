<?php

namespace App\Service;

class TournamentAssistantService
{
    private string $apiKey;

    public function __construct(string $geminiApiKey)
    {
        $this->apiKey = $geminiApiKey;
    }

    public function chat(string $userMessage, array $history = [], string $context = '', ?array $userProfile = null): string
    {
        $messages = [];

        // Prompt système pour recommandation intelligente
        $systemPrompt = $this->getSystemPrompt($userProfile);
        
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt . "\n\n" . $context
        ];

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

    private function getSystemPrompt(?array $userProfile): string
    {
        $basePrompt = "Tu es un assistant expert en e-sport spécialisé dans la RECOMMANDATION INTELLIGENTE de tournois et jeux. Tu réponds en français.

🎯 TON RÔLE PRINCIPAL : RECOMMANDATION PERSONNALISÉE

Règles STRICTES :
1. ANALYSE le profil utilisateur (stats, équipes, historique)
2. RECOMMANDE des tournois adaptés à son niveau et ses préférences
3. EXPLIQUE pourquoi tu recommandes ce tournoi (niveau, jeu favori, etc.)
4. SOIS PROACTIF : suggère des actions concrètes
5. Réponds de façon courte (3-4 lignes maximum)
6. Utilise des emojis pour rendre ça plus vivant (🎮 🏆 ⚡ 💪 🎯)

CAPACITÉS SPÉCIALES :
- Analyse du winrate pour recommander le niveau de tournoi adapté
- Détection du jeu favori pour suggérer des tournois pertinents
- Vérification des places disponibles dans les tournois
- Alerte sur les dates limites d'inscription
- Suggestion de création d'équipe si l'utilisateur n'en a pas
- Recommandation de rejoindre une équipe existante

EXEMPLES DE RECOMMANDATIONS :
- Winrate > 60% → Tournois compétitifs/avancés
- Winrate 40-60% → Tournois intermédiaires
- Winrate < 40% ou débutant → Tournois pour débutants/gratuits
- Jeu favori détecté → Prioriser les tournois de ce jeu
- Pas d'équipe → Suggérer création ou rejoindre une équipe
- Équipe incomplète → Suggérer recrutement de membres

STYLE DE COMMUNICATION :
- Enthousiaste et motivant
- Personnalisé selon le profil
- Concret avec des chiffres et faits
- Toujours proposer une action claire";

        // Ajouter des informations sur le profil utilisateur si disponible
        if ($userProfile) {
            $basePrompt .= "\n\n📊 PROFIL UTILISATEUR ACTUEL :\n";
            $basePrompt .= "- Nom: {$userProfile['username']}\n";
            $basePrompt .= "- Équipes: " . count($userProfile['teams']) . "\n";
            $basePrompt .= "- Matchs joués: {$userProfile['stats']['total_matches']}\n";
            $basePrompt .= "- Winrate: {$userProfile['stats']['winrate']}%\n";
            
            if ($userProfile['stats']['favorite_game']) {
                $basePrompt .= "- Jeu favori: {$userProfile['stats']['favorite_game']}\n";
            }

            // Déterminer le niveau du joueur
            $winrate = $userProfile['stats']['winrate'];
            $totalMatches = $userProfile['stats']['total_matches'];
            
            if ($totalMatches === 0) {
                $basePrompt .= "\n🆕 NIVEAU: DÉBUTANT (aucun match joué)\n";
                $basePrompt .= "→ Recommande des tournois gratuits pour débutants\n";
            } elseif ($winrate >= 60) {
                $basePrompt .= "\n⭐ NIVEAU: AVANCÉ (winrate {$winrate}%)\n";
                $basePrompt .= "→ Recommande des tournois compétitifs et payants\n";
            } elseif ($winrate >= 40) {
                $basePrompt .= "\n📈 NIVEAU: INTERMÉDIAIRE (winrate {$winrate}%)\n";
                $basePrompt .= "→ Recommande des tournois de niveau moyen\n";
            } else {
                $basePrompt .= "\n🎯 NIVEAU: EN PROGRESSION (winrate {$winrate}%)\n";
                $basePrompt .= "→ Recommande des tournois pour débutants et gratuits\n";
            }

            // Statut de l'équipe
            if (empty($userProfile['teams'])) {
                $basePrompt .= "\n⚠️ ALERTE: Utilisateur sans équipe\n";
                $basePrompt .= "→ Suggère de créer une équipe ou rejoindre une équipe existante\n";
            } else {
                $hasOwnership = false;
                foreach ($userProfile['teams'] as $team) {
                    if ($team['role'] === 'owner') {
                        $hasOwnership = true;
                        if ($team['members_count'] < $team['max_members']) {
                            $basePrompt .= "\n💡 INFO: Équipe '{$team['name']}' a des places disponibles ({$team['members_count']}/{$team['max_members']})\n";
                            $basePrompt .= "→ Suggère de recruter des membres\n";
                        }
                    }
                }
            }
        } else {
            $basePrompt .= "\n\n⚠️ Utilisateur NON CONNECTÉ\n";
            $basePrompt .= "→ Recommande de se connecter pour des suggestions personnalisées\n";
            $basePrompt .= "→ Montre quand même les tournois disponibles de manière générale\n";
        }

        return $basePrompt;
    }
}
