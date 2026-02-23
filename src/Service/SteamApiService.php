<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SteamApiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private const BASE_URL = 'https://api.steampowered.com';

    // App IDs
    private const CS2_APP_ID = 730;
    private const FIFA_APP_ID = 2195250; // FIFA 24
    private const EFOOTBALL_APP_ID = 1665460;

    public function __construct(HttpClientInterface $httpClient, string $steamApiKey = '')
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $steamApiKey;
    }

    /**
     * Résout un Steam ID à partir d'une vanity URL
     */
    public function resolveVanityUrl(string $vanityUrl): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . '/ISteamUser/ResolveVanityURL/v1/', [
                'query' => [
                    'key' => $this->apiKey,
                    'vanityurl' => $vanityUrl,
                ],
            ]);

            $data = $response->toArray();
            
            if ($data['response']['success'] === 1) {
                return $data['response']['steamid'];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Steam API Error (resolveVanityUrl): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère le profil d'un joueur
     */
    public function getPlayerSummary(string $steamId): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . '/ISteamUser/GetPlayerSummaries/v2/', [
                'query' => [
                    'key' => $this->apiKey,
                    'steamids' => $steamId,
                ],
            ]);

            $data = $response->toArray();
            
            if (isset($data['response']['players'][0])) {
                return $data['response']['players'][0];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Steam API Error (getPlayerSummary): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les statistiques d'un jeu
     */
    public function getUserStatsForGame(string $steamId, int $appId): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . '/ISteamUserStats/GetUserStatsForGame/v2/', [
                'query' => [
                    'key' => $this->apiKey,
                    'steamid' => $steamId,
                    'appid' => $appId,
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            error_log("Steam API Error (getUserStatsForGame): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les jeux possédés par un joueur
     */
    public function getOwnedGames(string $steamId): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 
                self::BASE_URL . '/IPlayerService/GetOwnedGames/v1/', [
                'query' => [
                    'key' => $this->apiKey,
                    'steamid' => $steamId,
                    'include_appinfo' => 1,
                    'include_played_free_games' => 1,
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            error_log("Steam API Error (getOwnedGames): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les statistiques CS2
     */
    public function getCS2Stats(string $steamId): ?array
    {
        $profile = $this->getPlayerSummary($steamId);
        $stats = $this->getUserStatsForGame($steamId, self::CS2_APP_ID);
        $games = $this->getOwnedGames($steamId);

        $playtime = 0;
        if ($games && isset($games['response']['games'])) {
            foreach ($games['response']['games'] as $game) {
                if ($game['appid'] === self::CS2_APP_ID) {
                    $playtime = $game['playtime_forever'] ?? 0;
                    break;
                }
            }
        }

        return [
            'profile' => $profile,
            'stats' => $stats,
            'playtime' => $playtime,
        ];
    }

    /**
     * Récupère les statistiques FIFA
     */
    public function getFIFAStats(string $steamId): ?array
    {
        $profile = $this->getPlayerSummary($steamId);
        $stats = $this->getUserStatsForGame($steamId, self::FIFA_APP_ID);
        $games = $this->getOwnedGames($steamId);

        $playtime = 0;
        if ($games && isset($games['response']['games'])) {
            foreach ($games['response']['games'] as $game) {
                if ($game['appid'] === self::FIFA_APP_ID) {
                    $playtime = $game['playtime_forever'] ?? 0;
                    break;
                }
            }
        }

        return [
            'profile' => $profile,
            'stats' => $stats,
            'playtime' => $playtime,
        ];
    }

    /**
     * Récupère les statistiques eFootball
     */
    public function getEFootballStats(string $steamId): ?array
    {
        $profile = $this->getPlayerSummary($steamId);
        $stats = $this->getUserStatsForGame($steamId, self::EFOOTBALL_APP_ID);
        $games = $this->getOwnedGames($steamId);

        $playtime = 0;
        if ($games && isset($games['response']['games'])) {
            foreach ($games['response']['games'] as $game) {
                if ($game['appid'] === self::EFOOTBALL_APP_ID) {
                    $playtime = $game['playtime_forever'] ?? 0;
                    break;
                }
            }
        }

        return [
            'profile' => $profile,
            'stats' => $stats,
            'playtime' => $playtime,
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
