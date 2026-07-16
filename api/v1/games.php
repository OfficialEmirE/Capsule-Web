<?php
declare(strict_types=1);

// Oturum kontrolü için session'ı başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php'; 

/**
 * Main API Gateway triggered by index.php
 */
function handleGamesApi(string $apiAction): void
{
    ob_start();
    header('Content-Type: application/json; charset=utf-8');
    
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    try {
        $db = api_db(); 

        switch ($apiAction) {
            case '': 
                if (ob_get_length()) ob_end_clean();
                header("Location: /api/v1", true, 302);
                exit;

            case 'list':
                handleList($db, $method);
                break;

            case 'info':
                handleInfo($db, $method);
                break;

            case 'create':
                handleCreate($db, $method);
                break;

            case 'update':
                handleUpdate($db, $method);
                break;

            case 'search':
                handleSearch($db, $method);
                break;

            default:
                throw new Exception('Invalid API action.', 404);
        }

    } catch (Exception $e) {
        if (ob_get_length()) ob_end_clean();
        $errorCode = $e->getCode();
        $httpCode = (is_numeric($errorCode) && $errorCode >= 400 && $errorCode <= 505) ? (int)$errorCode : 500;
        
        http_response_code($httpCode);
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| CORE API FUNCTIONS
|--------------------------------------------------------------------------
*/

/**
 * List active games (Chronologically descending: Newest first)
 */
function handleList(PDO $db, string $method): void
{
    if ($method !== 'GET') throw new Exception('Method Not Allowed. Only GET requests are supported.', 405);

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    $limit = 12; 
    $offset = ($page - 1) * $limit;

    $totalStmt = $db->prepare("SELECT COUNT(*) FROM games WHERE public = :pub1 OR public = :pub2");
    $totalStmt->execute([':pub1' => 1, ':pub2' => '1']);
    $totalGames = (int)$totalStmt->fetchColumn();
    $totalPages = (int)ceil($totalGames / $limit);

    $sql = "SELECT * FROM games WHERE public = 1 OR public = '1' ORDER BY id DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    $stmt = $db->query($sql);
    
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($games === false) {
        $games = [];
    }

    foreach ($games as &$game) {
        if (isset($game['thumbnail_urls']) && !empty($game['thumbnail_urls'])) {
            $decoded = json_decode($game['thumbnail_urls'], true);
            $game['thumbnail_urls'] = is_array($decoded) ? $decoded : [];
        } else {
            $game['thumbnail_urls'] = [];
        }
    }

    ob_end_clean();
    
    $response = [
        'status' => 'success',
        'pagination' => [
            'current_page' => $page, 
            'per_page' => $limit, 
            'total_games' => $totalGames, 
            'total_pages' => $totalPages
        ],
        'games' => $games
    ];

    $jsonOutput = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($jsonOutput === false) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'JSON Encoding Error: ' . json_last_error_msg(),
            'raw_games_count' => count($games)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo $jsonOutput;
    exit;
}

/**
 * Fetch detailed entity metadata for a specific game
 */
function handleInfo(PDO $db, string $method): void
{
    if ($method !== 'GET') throw new Exception('Method Not Allowed. Only GET requests are supported.', 405);

    $gameId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($gameId <= 0) throw new Exception('Invalid or missing game ID.', 400);

    $stmt = $db->prepare("SELECT * FROM games WHERE id = ? LIMIT 1");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) throw new Exception('Game entity not found.', 404);

    $game['thumbnail_urls'] = json_decode($game['thumbnail_urls'] ?? '[]', true);

    ob_end_clean();
    echo json_encode(['status' => 'success', 'game' => $game], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Provision and register a new game record (Oturum zorunlu)
 */
function handleCreate(PDO $db, string $method): void
{
    if ($method !== 'POST') throw new Exception('Method Not Allowed. Only POST requests are supported.', 405);

    // OTURUM KONTROLÜ
    $sessionUserId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    if (!$sessionUserId) {
        throw new Exception('Unauthorized: You must be logged in to create a game.', 401);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($data['name'])) {
        throw new Exception('Validation Failed: name property is required.', 400);
    }

    $thumbnails = $data['thumbnail_urls'] ?? [];
    if (count($thumbnails) > 8) throw new Exception('Array boundary exceeded: Maximum of 8 media structures allowed.', 400);

    $cleaned_thumbnails = [];
    foreach ($thumbnails as $media) {
        if (isset($media['type'], $media['value'])) {
            $cleaned_thumbnails[] = [
                'type'       => trim((string)$media['type']),
                'asset_type' => trim((string)($media['asset_type'] ?? 'game_thumbnail')),
                'value'      => trim((string)$media['value'])
            ];
        }
    }

    $sql = "INSERT INTO games (name, `desc`, ownerUserId, max_players, public, thumbnail_urls) 
            VALUES (:name, :desc, :ownerUserId, :max_players, :public, :thumbnail_urls)";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':name'           => trim($data['name']),
        ':desc'           => trim($data['desc'] ?? ''),
        ':ownerUserId'    => (int)$sessionUserId, // İstekte gelen değil, session'daki güvenli ID yazılıyor
        ':max_players'    => (int)($data['max_players'] ?? 10),
        ':public'         => (int)($data['public'] ?? 1),
        ':thumbnail_urls' => json_encode($cleaned_thumbnails, JSON_UNESCAPED_UNICODE)
    ]);

    ob_end_clean();
    http_response_code(201);
    echo json_encode([
        'status' => 'success', 
        'message' => 'Game entity provisioned successfully.', 
        'id' => $db->lastInsertId()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Mutate configuration layout of an existing game record (Sadece Sahibi Güncelleyebilir)
 */
function handleUpdate(PDO $db, string $method): void
{
    if ($method !== 'POST') throw new Exception('Method Not Allowed. Only POST requests are supported.', 405);

    // OTURUM KONTROLÜ
    $sessionUserId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    if (!$sessionUserId) {
        throw new Exception('Unauthorized: You must be logged in to update a game.', 401);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception('Validation Failed: Target instance id property is required.', 400);
    }

    $gameId = (int)$data['id'];

    $checkStmt = $db->prepare("SELECT * FROM games WHERE id = ? LIMIT 1");
    $checkStmt->execute([$gameId]);
    $currentGame = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentGame) {
        throw new Exception('Transaction Aborted: Target game instance not found. ID: ' . $gameId, 404);
    }

    // SAHİPLİK KONTROLÜ
    if ((int)$currentGame['ownerUserId'] !== (int)$sessionUserId) {
        throw new Exception('Forbidden: You do not have permission to modify this game. Only the owner can make changes.', 403);
    }

    $name       = (isset($data['name']) && trim((string)$data['name']) !== '') ? trim((string)$data['name']) : $currentGame['name'];
    $desc       = isset($data['desc']) ? trim((string)$data['desc']) : $currentGame['desc'];
    $maxPlayers = isset($data['max_players']) ? (int)$data['max_players'] : (int)$currentGame['max_players'];
    $public     = isset($data['public']) ? (int)$data['public'] : (int)$currentGame['public'];

    if (isset($data['thumbnail_urls']) && is_array($data['thumbnail_urls'])) {
        $thumbnails = $data['thumbnail_urls'];
        if (count($thumbnails) > 8) throw new Exception('Array boundary exceeded: Maximum of 8 media structures allowed.', 400);

        $cleaned_thumbnails = [];
        foreach ($thumbnails as $media) {
            if (isset($media['type'], $media['value'])) {
                $cleaned_thumbnails[] = [
                    'type'       => trim((string)$media['type']),
                    'asset_type' => trim((string)($media['asset_type'] ?? 'game_thumbnail')),
                    'value'      => trim((string)$media['value'])
                ];
            }
        }
        $thumbnail_json = json_encode($cleaned_thumbnails, JSON_UNESCAPED_UNICODE);
    } else {
        $thumbnail_json = $currentGame['thumbnail_urls'];
    }

    $sql = "UPDATE games 
            SET name = :name, 
                `desc` = :desc, 
                max_players = :max_players, 
                public = :public, 
                thumbnail_urls = :thumbnail_urls 
            WHERE id = :id";

    $stmt = $db->prepare($sql);
    
    $success = $stmt->execute([
        ':name'           => $name,
        ':desc'           => $desc,
        ':max_players'    => $maxPlayers,
        ':public'         => $public,
        ':thumbnail_urls' => $thumbnail_json,
        ':id'             => $gameId
    ]);

    if (!$success) {
        throw new Exception('Database Transaction Failed: Unable to commit mutation layout.', 500);
    }

    ob_end_clean();
    echo json_encode([
        'status' => 'success', 
        'message' => 'Game configuration mutated successfully.',
        'updated_fields' => [
            'id' => $gameId,
            'name' => $name,
            'public' => $public,
            'max_players' => $maxPlayers
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Search publicly visible games by name or description
 */
function handleSearch(PDO $db, string $method): void
{
    if ($method !== 'GET') {
        throw new Exception('Method Not Allowed. Only GET requests are supported.', 405);
    }

    $query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    if ($query === '') {
        throw new Exception('Validation Failed: Search query parameter "q" is required.', 400);
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    $limit = 12;
    $offset = ($page - 1) * $limit;

    $searchTerm = '%' . $query . '%';

    // Toplam eşleşen public oyun sayısını hesapla
    $totalStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM games 
        WHERE (public = 1 OR public = '1') 
          AND (name LIKE :q_name OR `desc` LIKE :q_desc)
    ");
    $totalStmt->execute([
        ':q_name' => $searchTerm,
        ':q_desc' => $searchTerm
    ]);
    $totalGames = (int)$totalStmt->fetchColumn();
    $totalPages = (int)ceil($totalGames / $limit);

    // Eşleşen kayıtları getir
    $sql = "
        SELECT * 
        FROM games 
        WHERE (public = 1 OR public = '1') 
          AND (name LIKE :q_name OR `desc` LIKE :q_desc)
        ORDER BY id DESC 
        LIMIT " . intval($limit) . " OFFSET " . intval($offset) . "
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':q_name' => $searchTerm,
        ':q_desc' => $searchTerm
    ]);
    
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Görsel dizilerini çöz
    foreach ($games as &$game) {
        if (isset($game['thumbnail_urls']) && !empty($game['thumbnail_urls'])) {
            $decoded = json_decode($game['thumbnail_urls'], true);
            $game['thumbnail_urls'] = is_array($decoded) ? $decoded : [];
        } else {
            $game['thumbnail_urls'] = [];
        }
    }

    ob_end_clean();
    
    echo json_encode([
        'status' => 'success',
        'query' => $query,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_games' => $totalGames,
            'total_pages' => $totalPages
        ],
        'games' => $games
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}