<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PubgApiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private const BASE_URL = 'https://api.pubg.com';

    public function __construct(HttpClientInterface $httpClient, string $pubgApiKey = '')
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $pubgApiKey;
    }

    /**
     * Recherche un joueur par nom
     */
    public function searchPlayer(string $playerName, string $platform = 'steam'): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . "/shards/{$platform}/players", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/vnd.api+json',
                ],
                'query' => [
                    'filter[playerNames]' => $playerName,
                ],
            ]);

            $data = $response->toArray();
            
            if (isset($data['data'][0])) {
                return $data['data'][0];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("PUBG API Error (searchPlayer): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les statistiques de saison
     */
    public function getSeasonStats(string $accountId, string $seasonId, string $platform = 'steam'): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . "/shards/{$platform}/players/{$accountId}/seasons/{$seasonId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/vnd.api+json',
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            error_log("PUBG API Error (getSeasonStats): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les statistiques ranked
     */
    public function getRankedStats(string $accountId, string $seasonId, string $platform = 'steam'): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . "/shards/{$platform}/players/{$accountId}/seasons/{$seasonId}/ranked", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/vnd.api+json',
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            error_log("PUBG API Error (getRankedStats): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les saisons disponibles
     */
    public function getSeasons(string $platform = 'steam'): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . "/shards/{$platform}/seasons", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/vnd.api+json',
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            error_log("PUBG API Error (getSeasons): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les statistiques complètes d'un joueur
     */
    public function getPlayerStats(string $playerName, string $platform = 'steam'): ?array
    {
        $player = $this->searchPlayer($playerName, $platform);
        
        if (!$player || !isset($player['id'])) {
            return null;
        }

        $seasons = $this->getSeasons($platform);
        $currentSeasonId = null;
        
        if ($seasons && isset($seasons['data'])) {
            // Prendre la dernière saison
            $currentSeasonId = end($seasons['data'])['id'] ?? null;
        }

        $seasonStats = null;
        $rankedStats = null;
        
        if ($currentSeasonId) {
            $seasonStats = $this->getSeasonStats($player['id'], $currentSeasonId, $platform);
            $rankedStats = $this->getRankedStats($player['id'], $currentSeasonId, $platform);
        }

        return [
            'player' => $player,
            'seasonStats' => $seasonStats,
            'rankedStats' => $rankedStats,
            'currentSeasonId' => $currentSeasonId,
        ];
    }

    /**
     * Vérifie si l'API key est configurée
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
