<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class TeamExtractionService
{
    // LoL Esports API (gratuite, clé publique)
    private const LOL_API_URL = 'https://esports-api.lolesports.com/persisted/gw/getTeams';
    private const LOL_API_KEY = '0TvQnueqKa5mxJntVWt0w4LpLfEkrV1Ta8rQBb9Z';

    // VLR.gg API pour Valorant (communautaire, gratuite)
    private const VALORANT_API_URL = 'https://vlrggapi.vercel.app/rankings';

    // OpenDota API pour Dota 2 (gratuite, pas de clé)
    private const OPENDOTA_API_URL = 'https://api.opendota.com/api/teams';

    // Limite d'équipes importées par extraction
    private const MAX_TEAMS_PER_IMPORT = 5;

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * Résout le slug du jeu à partir de son nom.
     */
    public function resolveGameSlug(string $gameName): string
    {
        $name = mb_strtolower(trim($gameName));

        if (str_contains($name, 'league') || str_contains($name, 'lol')) {
            return 'lol';
        }
        if (str_contains($name, 'valorant')) {
            return 'valorant';
        }
        if (str_contains($name, 'counter') || str_contains($name, 'cs2') || str_contains($name, 'cs:go') || str_contains($name, 'csgo')) {
            return 'cs2';
        }
        if (str_contains($name, 'pubg') || str_contains($name, 'battleground')) {
            return 'pubg';
        }
        if (str_contains($name, 'fifa') || str_contains($name, 'football') || str_contains($name, 'efootball')) {
            return 'football';
        }
        if (str_contains($name, 'fortnite')) {
            return 'fortnite';
        }
        if (str_contains($name, 'dota')) {
            return 'dota2';
        }
        if (str_contains($name, 'overwatch')) {
            return 'overwatch';
        }
        if (str_contains($name, 'rocket')) {
            return 'rl';
        }

        return 'unknown';
    }

    /**
     * Extrait les équipes depuis l'API correspondante au jeu.
     *
     * @param User   $owner    L'admin qui déclenche l'extraction (sera le owner des équipes)
     * @param string $gameName Le nom du jeu
     * @return array{created: int, skipped: int, errors: string[], game: string}
     */
    public function extractTeams(User $owner, string $gameName): array
    {
        $slug = $this->resolveGameSlug($gameName);
        $this->logger->info('Team extraction started', ['game' => $gameName, 'slug' => $slug]);

        return match ($slug) {
            'lol'       => $this->extractLoLTeams($owner),
            'valorant'  => $this->extractValorantTeams($owner),
            'cs2'       => $this->extractCS2Teams($owner),
            'dota2'     => $this->extractDota2Teams($owner),
            default     => $this->extractViaLoLEsports($owner, $gameName),
        };
    }

    // =====================================================================
    //  LEAGUE OF LEGENDS — LoL Esports API
    // =====================================================================

    private function extractLoLTeams(User $owner): array
    {
        $result = $this->initResult('League of Legends');

        try {
            $response = $this->httpClient->request('GET', self::LOL_API_URL, [
                'headers' => ['x-api-key' => self::LOL_API_KEY],
                'query'   => ['hl' => 'en-US'],
                'timeout' => 20,
                'verify_peer' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                $result['errors'][] = "L'API LoL Esports a retourné le code HTTP " . $response->getStatusCode();
                return $result;
            }

            $data = $response->toArray();
            $teams = $data['data']['teams'] ?? [];

            if (empty($teams)) {
                $result['errors'][] = "Aucune équipe LoL trouvée dans la réponse de l'API.";
                return $result;
            }

            // Filtrer : exclure "TBD", garder seulement les équipes actives avec un vrai nom
            $teams = array_filter($teams, function ($t) {
                $name = $t['name'] ?? '';
                $status = $t['status'] ?? '';
                return $name !== 'TBD'
                    && !empty($name)
                    && mb_strlen($name) <= 100
                    && $status === 'active';
            });

            // Limiter le nombre d'équipes
            $teams = array_slice($teams, 0, self::MAX_TEAMS_PER_IMPORT);

            $this->importTeams($owner, $teams, $result, 'name', 'image');
        } catch (\Exception $e) {
            $result['errors'][] = "Erreur API LoL : " . $e->getMessage();
        }

        return $result;
    }

    // =====================================================================
    //  VALORANT — VLR.gg Community API
    // =====================================================================

    private function extractValorantTeams(User $owner): array
    {
        $result = $this->initResult('Valorant');
        $regions = ['eu', 'na', 'ap', 'la', 'oce'];

        try {
            $allTeams = [];

            foreach ($regions as $region) {
                try {
                    $response = $this->httpClient->request('GET', self::VALORANT_API_URL, [
                        'query'   => ['region' => $region],
                        'timeout' => 15,
                        'verify_peer' => false,
                    ]);

                    $statusCode = $response->getStatusCode();
                    $this->logger->info('VLR API response', ['region' => $region, 'status' => $statusCode]);
                    if ($statusCode === 200) {
                        $data = $response->toArray();
                        $teams = $data['data'] ?? [];
                        $this->logger->info('VLR teams found', ['region' => $region, 'count' => count($teams)]);
                        foreach ($teams as $team) {
                            $name = $team['team'] ?? null;
                            if ($name && !isset($allTeams[mb_strtolower($name)])) {
                                $logo = $team['logo'] ?? null;
                                // Corriger les URLs relatives
                                if ($logo && str_starts_with($logo, '//')) {
                                    $logo = 'https:' . $logo;
                                }
                                $allTeams[mb_strtolower($name)] = [
                                    'name' => $name,
                                    'logo' => $logo,
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->error('VLR API error', ['region' => $region, 'error' => $e->getMessage()]);
                    // Continuer avec les autres régions
                }
            }

            if (empty($allTeams)) {
                $result['errors'][] = "Aucune équipe Valorant trouvée via l'API VLR.";
                return $result;
            }

            // Limiter
            $allTeams = array_slice($allTeams, 0, self::MAX_TEAMS_PER_IMPORT);

            $existingNames = $this->getExistingTeamNames();

            foreach ($allTeams as $teamInfo) {
                $teamName = $teamInfo['name'];
                if (mb_strlen($teamName) > 100) {
                    continue;
                }

                if (in_array(mb_strtolower($teamName), $existingNames, true)) {
                    $result['skipped']++;
                    continue;
                }

                $this->createEquipe($owner, $teamName, $teamInfo['logo']);
                $result['created']++;
            }

            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Valorant extraction failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $result['errors'][] = "Erreur API Valorant : " . $e->getMessage();
        }

        $this->logger->info('Valorant extraction result', $result);
        return $result;
    }

    // =====================================================================
    //  CS2 — Utilise l'API LoL Esports comme source générique d'équipes esport
    //  (beaucoup d'organisations esport ont des équipes multi-jeux)
    // =====================================================================

    private function extractCS2Teams(User $owner): array
    {
        $result = $this->initResult('Counter-Strike 2');

        try {
            // Les organisations esport majeures de CS2 sont aussi dans l'API LoL Esports
            // On va fetch les équipes LoL Esports actives (orgs multi-jeux)
            $response = $this->httpClient->request('GET', self::LOL_API_URL, [
                'headers' => ['x-api-key' => self::LOL_API_KEY],
                'query'   => ['hl' => 'en-US'],
                'timeout' => 20,
                'verify_peer' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                $result['errors'][] = "L'API a retourné le code HTTP " . $response->getStatusCode();
                return $result;
            }

            $data = $response->toArray();
            $teams = $data['data']['teams'] ?? [];

            // Filtrer les organisations esport connues en CS2
            $cs2OrgKeywords = [
                'natus vincere', 'navi', 'g2', 'faze', 'cloud9', 'vitality',
                'heroic', 'mouz', 'mousesports', 'liquid', 'complexity',
                'astralis', 'ninjas in pyjamas', 'nip', 'fnatic', 'virtus.pro',
                'ence', 'big', 'furia', 'imperial', '9z', 'pain',
                'eternal fire', 'monte', 'spirit', 'outsiders', 'apeks',
                'og', 'fluxo', 'legacy', 'sharks', 'mibr',
                'tyloo', 'lynn vision', 'grayhound', 'rooster',
                'bet boom', 'aurora', 'falcons', 'theboss',
            ];

            $filtered = array_filter($teams, function ($t) use ($cs2OrgKeywords) {
                $name = mb_strtolower($t['name'] ?? '');
                $slug = mb_strtolower($t['slug'] ?? '');
                if ($name === 'tbd' || empty($name)) return false;

                foreach ($cs2OrgKeywords as $keyword) {
                    if (str_contains($name, $keyword) || str_contains($slug, $keyword)) {
                        return true;
                    }
                }
                return false;
            });

            // Si pas assez, prendre les premières équipes actives
            if (count($filtered) < 20) {
                $activeTeams = array_filter($teams, fn($t) =>
                    ($t['name'] ?? '') !== 'TBD' && !empty($t['name']) && ($t['status'] ?? '') === 'active'
                );
                $filtered = array_slice($activeTeams, 0, self::MAX_TEAMS_PER_IMPORT);
            } else {
                $filtered = array_slice($filtered, 0, self::MAX_TEAMS_PER_IMPORT);
            }

            $this->importTeams($owner, $filtered, $result, 'name', 'image');
        } catch (\Exception $e) {
            $result['errors'][] = "Erreur API CS2 : " . $e->getMessage();
        }

        return $result;
    }

    // =====================================================================
    //  DOTA 2 — OpenDota API (gratuite, pas de clé)
    // =====================================================================

    private function extractDota2Teams(User $owner): array
    {
        $result = $this->initResult('Dota 2');

        try {
            $response = $this->httpClient->request('GET', self::OPENDOTA_API_URL, [
                'timeout' => 15,
                'verify_peer' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                $result['errors'][] = "L'API OpenDota a retourné le code HTTP " . $response->getStatusCode();
                return $result;
            }

            $data = $response->toArray();

            if (empty($data)) {
                $result['errors'][] = "Aucune équipe Dota 2 trouvée.";
                return $result;
            }

            // Prendre les premières équipes (déjà triées par rating)
            $teams = array_slice($data, 0, self::MAX_TEAMS_PER_IMPORT);

            $existingNames = $this->getExistingTeamNames();

            foreach ($teams as $teamData) {
                $teamName = $teamData['name'] ?? null;
                if (!$teamName || mb_strlen($teamName) > 100) {
                    continue;
                }

                if (in_array(mb_strtolower($teamName), $existingNames, true)) {
                    $result['skipped']++;
                    continue;
                }

                $logoUrl = $teamData['logo_url'] ?? null;
                $this->createEquipe($owner, $teamName, $logoUrl);
                $result['created']++;
            }

            $this->entityManager->flush();
        } catch (\Exception $e) {
            $result['errors'][] = "Erreur API Dota 2 : " . $e->getMessage();
        }

        return $result;
    }

    // =====================================================================
    //  FALLBACK — Utilise LoL Esports API pour les autres jeux
    //  (les orgs esport sont multi-jeux : PUBG, Fortnite, Overwatch, etc.)
    // =====================================================================

    private function extractViaLoLEsports(User $owner, string $gameName): array
    {
        $result = $this->initResult($gameName);

        try {
            $response = $this->httpClient->request('GET', self::LOL_API_URL, [
                'headers' => ['x-api-key' => self::LOL_API_KEY],
                'query'   => ['hl' => 'en-US'],
                'timeout' => 20,
                'verify_peer' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                $result['errors'][] = "L'API Esports a retourné le code HTTP " . $response->getStatusCode();
                return $result;
            }

            $data = $response->toArray();
            $teams = $data['data']['teams'] ?? [];

            // Filtrer les équipes actives avec un vrai nom
            $teams = array_filter($teams, fn($t) =>
                ($t['name'] ?? '') !== 'TBD'
                && !empty($t['name'])
                && mb_strlen($t['name']) <= 100
                && ($t['status'] ?? '') === 'active'
            );

            $teams = array_slice($teams, 0, self::MAX_TEAMS_PER_IMPORT);

            if (empty($teams)) {
                $result['errors'][] = "Aucune équipe trouvée pour « {$gameName} ».";
                return $result;
            }

            $this->importTeams($owner, $teams, $result, 'name', 'image');
        } catch (\Exception $e) {
            $result['errors'][] = "Erreur API pour {$gameName} : " . $e->getMessage();
        }

        return $result;
    }

    // =====================================================================
    //  Méthodes utilitaires
    // =====================================================================

    /**
     * Initialise le tableau de résultat.
     */
    private function initResult(string $gameName): array
    {
        return [
            'created' => 0,
            'skipped' => 0,
            'errors'  => [],
            'game'    => $gameName,
        ];
    }

    /**
     * Importe une liste d'équipes depuis un tableau de données API.
     */
    private function importTeams(User $owner, array $teams, array &$result, string $nameKey, string $logoKey): void
    {
        $existingNames = $this->getExistingTeamNames();

        foreach ($teams as $teamData) {
            $teamName = $teamData[$nameKey] ?? null;
            if (!$teamName || mb_strlen($teamName) > 100) {
                continue;
            }

            if (in_array(mb_strtolower($teamName), $existingNames, true)) {
                $result['skipped']++;
                continue;
            }

            $logoUrl = $teamData[$logoKey] ?? null;
            $this->createEquipe($owner, $teamName, $logoUrl);
            $result['created']++;
            $existingNames[] = mb_strtolower($teamName);
        }

        $this->entityManager->flush();
    }

    /**
     * Crée et persiste une entité Equipe.
     */
    private function createEquipe(User $owner, string $name, ?string $logoUrl): void
    {
        $equipe = new Equipe();
        $equipe->setNom($name);
        $equipe->setOwner($owner);
        $equipe->setMaxMembers(5);

        if ($logoUrl) {
            $logoFilename = $this->downloadLogo($logoUrl, $name);
            if ($logoFilename) {
                $equipe->setLogo($logoFilename);
            }
        }

        $this->entityManager->persist($equipe);
    }

    /**
     * Récupère les noms des équipes existantes en minuscules.
     */
    private function getExistingTeamNames(): array
    {
        $equipes = $this->entityManager->getRepository(Equipe::class)->findAll();
        return array_map(
            fn(Equipe $e) => mb_strtolower($e->getNom()),
            $equipes
        );
    }

    /**
     * Télécharge le logo d'une équipe et le sauvegarde dans le dossier uploads/teams.
     */
    private function downloadLogo(string $url, string $teamName): ?string
    {
        try {
            // Corriger les URLs relatives
            if (str_starts_with($url, '//')) {
                $url = 'https:' . $url;
            }

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 8,
                'verify_peer' => false,
            ]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $content = $response->getContent();
            $extension = $this->guessExtension($url, $response->getHeaders()['content-type'][0] ?? '');
            $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teamName);
            $filename = $safeFilename . '-' . uniqid() . '.' . $extension;

            $directory = dirname(__DIR__, 2) . '/public/uploads/teams';
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($directory . '/' . $filename, $content);

            return $filename;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Devine l'extension du fichier à partir de l'URL ou du Content-Type.
     */
    private function guessExtension(string $url, string $contentType): string
    {
        $mimeMap = [
            'image/png'     => 'png',
            'image/jpeg'    => 'jpg',
            'image/gif'     => 'gif',
            'image/webp'    => 'webp',
            'image/svg+xml' => 'svg',
        ];

        if (isset($mimeMap[$contentType])) {
            return $mimeMap[$contentType];
        }

        $path = parse_url($url, PHP_URL_PATH);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return $ext ?: 'png';
    }
}
