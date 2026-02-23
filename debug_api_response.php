<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$apiKey = $_ENV['RIOT_API_KEY'];
$httpClient = HttpClient::create();

// Étape 1: Récupérer le compte
$response1 = $httpClient->request('GET', 
    'https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/Caps/G2', [
    'headers' => ['X-Riot-Token' => $apiKey],
]);

$account = $response1->toArray();
echo "=== ACCOUNT DATA ===\n";
print_r($account);

// Étape 2: Récupérer le summoner
$puuid = $account['puuid'];
$response2 = $httpClient->request('GET', 
    "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/{$puuid}", [
    'headers' => ['X-Riot-Token' => $apiKey],
]);

echo "\n=== SUMMONER DATA ===\n";
$summoner = $response2->toArray();
print_r($summoner);

echo "\n=== CLÉS DISPONIBLES ===\n";
echo "Clés dans summoner: " . implode(', ', array_keys($summoner)) . "\n";
