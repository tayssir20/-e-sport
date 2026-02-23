<?php
/**
 * Test direct du service RiotApiService
 */

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$apiKey = $_ENV['RIOT_API_KEY'] ?? null;

echo "===========================================\n";
echo "TEST DIRECT DU SERVICE RIOT API\n";
echo "===========================================\n\n";

if (!$apiKey || $apiKey === 'RGAPI-your-api-key-here') {
    echo "❌ Clé API non configurée!\n";
    exit(1);
}

echo "✅ Clé API: " . substr($apiKey, 0, 15) . "...\n\n";

// Créer le service manuellement
$httpClient = HttpClient::create();

class TestRiotApiService
{
    private $httpClient;
    private $apiKey;
    private const BASE_URL = 'https://euw1.api.riotgames.com';
    private const REGION_URL = 'https://europe.api.riotgames.com';

    public function __construct($httpClient, $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function getAccountByRiotId(string $gameName, string $tagLine): ?array
    {
        try {
            echo "→ Appel API: getAccountByRiotId($gameName, $tagLine)\n";
            $url = self::REGION_URL . "/riot/account/v1/accounts/by-riot-id/{$gameName}/{$tagLine}";
            echo "  URL: $url\n";
            
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            echo "  Status: $statusCode\n";
            
            if ($statusCode === 200) {
                $data = $response->toArray();
                echo "  ✅ Succès! PUUID: " . substr($data['puuid'], 0, 20) . "...\n";
                return $data;
            }
            
            echo "  ❌ Échec (code $statusCode)\n";
            return null;
        } catch (\Exception $e) {
            echo "  ❌ Exception: " . $e->getMessage() . "\n";
            return null;
        }
    }

    public function getSummonerByPuuid(string $puuid): ?array
    {
        try {
            echo "\n→ Appel API: getSummonerByPuuid\n";
            $url = self::BASE_URL . "/lol/summoner/v4/summoners/by-puuid/{$puuid}";
            echo "  URL: $url\n";
            
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'X-Riot-Token' => $this->apiKey,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            echo "  Status: $statusCode\n";
            
            if ($statusCode === 200) {
                $data = $response->toArray();
                echo "  ✅ Succès! ID: " . $data['id'] . ", Level: " . $data['summonerLevel'] . "\n";
                return $data;
            }
            
            echo "  ❌ Échec (code $statusCode)\n";
            return null;
        } catch (\Exception $e) {
            echo "  ❌ Exception: " . $e->getMessage() . "\n";
            return null;
        }
    }

    public function getPlayerStats(string $gameName, string $tagLine): ?array
    {
        echo "\n=== TEST getPlayerStats ===\n";
        
        $account = $this->getAccountByRiotId($gameName, $tagLine);
        if (!$account || !isset($account['puuid'])) {
            echo "\n❌ ÉCHEC: Impossible de récupérer le compte\n";
            return null;
        }

        $summoner = $this->getSummonerByPuuid($account['puuid']);
        if (!$summoner || !isset($summoner['id'])) {
            echo "\n❌ ÉCHEC: Impossible de récupérer le summoner\n";
            return null;
        }

        echo "\n✅ SUCCÈS COMPLET!\n";
        return [
            'account' => $account,
            'summoner' => $summoner,
        ];
    }
}

$service = new TestRiotApiService($httpClient, $apiKey);

// Test avec Caps#G2
echo "Test avec: Caps#G2\n";
echo "===========================================\n";

$result = $service->getPlayerStats('Caps', 'G2');

if ($result) {
    echo "\n===========================================\n";
    echo "✅ LE SERVICE FONCTIONNE PARFAITEMENT!\n";
    echo "===========================================\n";
    echo "\nDonnées récupérées:\n";
    echo "- Nom: " . $result['account']['gameName'] . "#" . $result['account']['tagLine'] . "\n";
    echo "- Niveau: " . $result['summoner']['summonerLevel'] . "\n";
    echo "- PUUID: " . substr($result['account']['puuid'], 0, 30) . "...\n";
    echo "- Summoner ID: " . $result['summoner']['id'] . "\n";
} else {
    echo "\n===========================================\n";
    echo "❌ LE SERVICE NE FONCTIONNE PAS\n";
    echo "===========================================\n";
    echo "\nVérifiez les messages d'erreur ci-dessus.\n";
}
