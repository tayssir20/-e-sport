<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RiotApiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private const BASE_URL = 'https://euw1.api.riotgames.com';
    private const REGION_URL = 'https://europe.api.riotgames.com';

    public function __construct(HttpClientInterface $httpClient, string $riotApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $riotApiKey;
    }

    /**
     * Recherche un joueur par son nom et tag
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
            // Log l'erreur pour le débogage
            error_log("Riot API Error (getAccountByRiotId): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les informations du summoner par PUUID
     */
    public function getSummonerByPuuid(string $puuid): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . "/lol/summoner/v4/summoners/by-puuid/{$puuid}", [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Riot API Error (getSummonerByPuuid): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les informations de ranked du joueur
     */
    public function getLeagueEntries(string $puuid): ?array
    {
        try {
            // Utiliser l'endpoint by-puuid au lieu de by-summoner
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . "/lol/league/v4/entries/by-puuid/{$puuid}", [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Riot API Error (getLeagueEntries): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère l'historique des matchs
     */
    public function getMatchHistory(string $puuid, int $count = 20): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 
                self::REGION_URL . "/lol/match/v5/matches/by-puuid/{$puuid}/ids", [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
                'query' => [
                    'count' => $count,
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Récupère les détails d'un match
     */
    public function getMatchDetails(string $matchId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 
                self::REGION_URL . "/lol/match/v5/matches/{$matchId}", [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Récupère les statistiques complètes d'un joueur
     */
    public function getPlayerStats(string $gameName, string $tagLine): ?array
    {
        error_log("=== getPlayerStats START ===");
        error_log("gameName: $gameName, tagLine: $tagLine");
        
        $account = $this->getAccountByRiotId($gameName, $tagLine);
        error_log("account result: " . ($account ? json_encode($account) : 'NULL'));
        
        if (!$account || !isset($account['puuid'])) {
            error_log("ERREUR: account null ou pas de puuid");
            return null;
        }

        $summoner = $this->getSummonerByPuuid($account['puuid']);
        error_log("summoner result: " . ($summoner ? json_encode($summoner) : 'NULL'));
        
        if (!$summoner || !isset($summoner['puuid'])) {
            error_log("ERREUR: summoner null ou pas de puuid");
            return null;
        }

        // Utiliser le puuid au lieu de l'id
        $leagueEntries = $this->getLeagueEntries($account['puuid']);
        $matchHistory = $this->getMatchHistory($account['puuid'], 10);

        error_log("=== getPlayerStats SUCCESS ===");
        
        return [
            'account' => $account,
            'summoner' => $summoner,
            'leagueEntries' => $leagueEntries ?? [],
            'matchHistory' => $matchHistory ?? [],
        ];
    }
}
