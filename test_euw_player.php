<?php
/**
 * Test avec un joueur EUW
 */

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$apiKey = $_ENV['RIOT_API_KEY'] ?? null;

echo "Test avec un joueur EUW...\n\n";

// Test avec Caps (joueur pro européen)
$gameName = 'Caps';
$tagLine = 'G2';
$url = "https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/" . urlencode($gameName) . "/" . urlencode($tagLine);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Riot-Token: ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Recherche: {$gameName}#{$tagLine}\n";
echo "URL: {$url}\n";
echo "Code HTTP: {$httpCode}\n\n";

if ($httpCode === 200) {
    echo "✅ SUCCÈS! Joueur trouvé!\n";
    $data = json_decode($response, true);
    echo "Données:\n";
    print_r($data);
} else {
    echo "❌ Erreur {$httpCode}\n";
    echo "Réponse: {$response}\n\n";
    
    // Essayons avec un autre joueur
    echo "\nEssai avec un autre joueur...\n";
    $gameName = 'Rekkles';
    $tagLine = 'EUW';
    $url = "https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/" . urlencode($gameName) . "/" . urlencode($tagLine);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Riot-Token: ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Recherche: {$gameName}#{$tagLine}\n";
    echo "Code HTTP: {$httpCode}\n";
    
    if ($httpCode === 200) {
        echo "✅ SUCCÈS!\n";
        $data = json_decode($response, true);
        print_r($data);
    } else {
        echo "❌ Erreur {$httpCode}\n";
        echo "Réponse: {$response}\n";
    }
}
