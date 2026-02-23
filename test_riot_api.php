<?php
/**
 * Script de test pour vérifier que votre clé API Riot Games fonctionne
 * 
 * Usage: php test_riot_api.php
 */

// Charger les variables d'environnement
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Récupérer la clé API
$apiKey = $_ENV['RIOT_API_KEY'] ?? null;

echo "=================================================\n";
echo "   TEST DE LA CLÉ API RIOT GAMES\n";
echo "=================================================\n\n";

// Vérifier si la clé existe
if (!$apiKey) {
    echo "❌ ERREUR: La variable RIOT_API_KEY n'est pas définie dans .env\n";
    echo "\nSolution:\n";
    echo "1. Ouvrez le fichier .env\n";
    echo "2. Ajoutez: RIOT_API_KEY=votre-clé-ici\n";
    echo "3. Relancez ce script\n";
    exit(1);
}

// Vérifier si c'est le placeholder
if ($apiKey === 'RGAPI-your-api-key-here') {
    echo "❌ ERREUR: Vous utilisez le placeholder par défaut\n";
    echo "\nSolution:\n";
    echo "1. Allez sur https://developer.riotgames.com/\n";
    echo "2. Connectez-vous et copiez votre clé API\n";
    echo "3. Remplacez RGAPI-your-api-key-here dans .env\n";
    echo "4. Relancez ce script\n";
    exit(1);
}

echo "✅ Clé API trouvée: " . substr($apiKey, 0, 15) . "...\n\n";

// Test 1: Vérifier le format de la clé
echo "Test 1: Vérification du format de la clé...\n";
if (preg_match('/^RGAPI-[a-zA-Z0-9-]+$/', $apiKey)) {
    echo "✅ Format de la clé valide\n\n";
} else {
    echo "⚠️  Format de la clé suspect (mais peut fonctionner)\n\n";
}

// Test 2: Tester un appel API simple
echo "Test 2: Test d'un appel API (recherche de Faker)...\n";

$gameName = 'Hide on bush';
$tagLine = 'KR1';
$url = "https://asia.api.riotgames.com/riot/account/v1/accounts/by-riot-id/" . urlencode($gameName) . "/" . urlencode($tagLine);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Riot-Token: ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";

switch ($httpCode) {
    case 200:
        echo "✅ SUCCÈS! Votre clé API fonctionne parfaitement!\n";
        $data = json_decode($response, true);
        echo "\nDonnées récupérées:\n";
        echo "- Nom: " . ($data['gameName'] ?? 'N/A') . "#" . ($data['tagLine'] ?? 'N/A') . "\n";
        echo "- PUUID: " . substr($data['puuid'] ?? 'N/A', 0, 20) . "...\n";
        echo "\n✅ Votre intégration est prête à être utilisée!\n";
        break;
        
    case 403:
        echo "❌ ERREUR 403: Clé API invalide ou expirée\n";
        echo "\nSolution:\n";
        echo "1. Allez sur https://developer.riotgames.com/\n";
        echo "2. Cliquez sur 'REGENERATE API KEY'\n";
        echo "3. Copiez la nouvelle clé\n";
        echo "4. Mettez à jour .env\n";
        echo "5. Relancez ce script\n";
        break;
        
    case 404:
        echo "⚠️  Le joueur de test n'a pas été trouvé (normal si la région est différente)\n";
        echo "Mais votre clé API semble valide!\n";
        break;
        
    case 429:
        echo "⚠️  ERREUR 429: Trop de requêtes\n";
        echo "Attendez quelques secondes et réessayez\n";
        break;
        
    default:
        echo "❌ ERREUR $httpCode\n";
        echo "Réponse: $response\n";
        break;
}

echo "\n=================================================\n";
echo "   FIN DU TEST\n";
echo "=================================================\n";
