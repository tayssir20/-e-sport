<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ValorantApiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private const REGION_URL = 'https://europe.api.riotgames.com';
    private const PLATFORM_URL = 'https://eu.api.riotgames.com';
    private const HENRIK_API_URL = 'https://api.henrikdev.xyz/valorant/v3';

    public function __construct(HttpClientInterface $httpClient, string $riotApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $riotApiKey;
    }

    /**
     * Recherche un compte par Riot ID
     */
    public function getAccountByRiotId(string $gameName, string $tagLine): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 
                self::REGION_URL . "/riot/account/v1/accounts/by-riot-id/{$gameName}/{$tagLine}", [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Valorant API Error (getAccountByRiotId): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère le MMR et le rang du joueur
     */
    public function getPlayerMMR(string $puuid): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 
                self::PLATFORM_URL . "/val/ranked/v1/by-puuid/{$puuid}", [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Valorant API Error (getPlayerMMR): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère l'historique des matchs via Henrik API (API non officielle)
     */
    public function getMatchHistory(string $puuid): ?array
    {
        try {
            // Utiliser Henrik API car l'API officielle Riot ne fournit pas l'historique
            $response = $this->httpClient->request('GET', 
                'https://api.henrikdev.xyz/valorant/v1/by-puuid/lifetime/matches/eu/' . $puuid, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $data['data'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            error_log("Valorant API Error (getMatchHistory): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails d'un match via Henrik API
     */
    public function getMatchDetails(string $matchId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 
                'https://api.henrikdev.xyz/valorant/v2/match/' . $matchId, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $data['data'] ?? null;
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Valorant API Error (getMatchDetails): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère l'historique des matchs récents via Henrik API
     */
    public function getRecentMatches(string $gameName, string $tagLine, string $region = 'eu'): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 
                "https://api.henrikdev.xyz/valorant/v3/matches/{$region}/{$gameName}/{$tagLine}", [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $data['data'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            error_log("Valorant API Error (getRecentMatches): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques complètes d'un joueur
     */
    public function getPlayerStats(string $gameName, string $tagLine): ?array
    {
        $account = $this->getAccountByRiotId($gameName, $tagLine);
        
        if (!$account || !isset($account['puuid'])) {
            return null;
        }

        $mmr = $this->getPlayerMMR($account['puuid']);
        $matchHistory = $this->getRecentMatches($gameName, $tagLine);

        return [
            'account' => $account,
            'mmr' => $mmr,
            'matchHistory' => $matchHistory,
        ];
    }

    /**
     * Convertit le tier numérique en nom de rang
     */
    public function getTierName(int $tier): string
    {
        $tiers = [
            0 => 'Unranked',
            3 => 'Iron 1', 4 => 'Iron 2', 5 => 'Iron 3',
            6 => 'Bronze 1', 7 => 'Bronze 2', 8 => 'Bronze 3',
            9 => 'Silver 1', 10 => 'Silver 2', 11 => 'Silver 3',
            12 => 'Gold 1', 13 => 'Gold 2', 14 => 'Gold 3',
            15 => 'Platinum 1', 16 => 'Platinum 2', 17 => 'Platinum 3',
            18 => 'Diamond 1', 19 => 'Diamond 2', 20 => 'Diamond 3',
            21 => 'Ascendant 1', 22 => 'Ascendant 2', 23 => 'Ascendant 3',
            24 => 'Immortal 1', 25 => 'Immortal 2', 26 => 'Immortal 3',
            27 => 'Radiant',
        ];

        return $tiers[$tier] ?? 'Unknown';
    }
}
