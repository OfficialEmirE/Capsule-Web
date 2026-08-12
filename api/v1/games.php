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

            case 'engagement':
                handleEngagement($db, $method);
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

function addDwfAssetId(array &$game): void
{
    $dwfId = (int)($game['dwf_id'] ?? 0);
    $cleanMedia = [];
    foreach ((array)($game['thumbnail_urls'] ?? []) as $media) {
        if (is_array($media) && strtolower((string)($media['type'] ?? '')) === 'dwf' && (int)($media['id'] ?? 0) > 0) {
            if ($dwfId <= 0) $dwfId = (int)$media['id'];
            continue;
        }
        $cleanMedia[] = $media;
    }
    $game['thumbnail_urls'] = $cleanMedia;
    $game['dwf_id'] = $dwfId > 0 ? $dwfId : null;
}

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
    $ownerId = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : 0;
    $where = "(public = 1 OR public = '1') AND NOT EXISTS (
        SELECT 1 FROM user_bans ban_filter
        WHERE ban_filter.user_id = games.ownerUserId
          AND ban_filter.is_active = 1
          AND (ban_filter.expires_at IS NULL OR ban_filter.expires_at > NOW())
    )";
    $countParams = [];

    if ($ownerId > 0) {
        $where = "(public = 1 OR public = '1') AND ownerUserId = :owner_id AND NOT EXISTS (
            SELECT 1 FROM user_bans ban_filter
            WHERE ban_filter.user_id = games.ownerUserId
              AND ban_filter.is_active = 1
              AND (ban_filter.expires_at IS NULL OR ban_filter.expires_at > NOW())
        )";
        $countParams[':owner_id'] = $ownerId;
    }

    $totalStmt = $db->prepare("SELECT COUNT(*) FROM games WHERE {$where}");
    $totalStmt->execute($countParams);
    $totalGames = (int)$totalStmt->fetchColumn();
    $totalPages = (int)ceil($totalGames / $limit);

    $sql = "SELECT * FROM games WHERE {$where} ORDER BY id DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    $stmt = $db->prepare($sql);
    $stmt->execute($countParams);
    
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
        addDwfAssetId($game);
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

    $banCheck = $db->prepare("SELECT 1 FROM user_bans WHERE user_id = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
    $banCheck->execute([(int)$game['ownerUserId']]);
    if ($banCheck->fetchColumn()) throw new Exception('Game entity not found.', 404);

    $game['thumbnail_urls'] = json_decode($game['thumbnail_urls'] ?? '[]', true);
    if (!is_array($game['thumbnail_urls'])) $game['thumbnail_urls'] = [];
    addDwfAssetId($game);

    ob_end_clean();
    echo json_encode(['status' => 'success', 'game' => $game], JSON_UNESCAPED_UNICODE);
    exit;
}

function ensureEngagementTables(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS game_visits (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        game_id INT NOT NULL,
        visitor_user_id INT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX game_visits_game_id_idx (game_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS game_votes (
        game_id INT NOT NULL,
        user_id INT NOT NULL,
        vote ENUM('like', 'dislike') NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (game_id, user_id),
        INDEX game_votes_user_id_idx (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function handleEngagement(PDO $db, string $method): void
{
    ensureEngagementTables($db);
    if ($method === 'GET' && isset($_GET['ids'])) {
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$_GET['ids'])), static fn (int $id): bool => $id > 0)));
        if (!$ids) throw new Exception('Invalid or missing game IDs.', 400);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT game_id, SUM(vote = 'like') AS likes, SUM(vote = 'dislike') AS dislikes FROM game_votes WHERE game_id IN ({$placeholders}) GROUP BY game_id");
        $stmt->execute($ids);
        $engagements = [];
        foreach ($ids as $id) $engagements[(string)$id] = ['likes' => 0, 'dislikes' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $engagements[(string)(int)$row['game_id']] = ['likes' => (int)$row['likes'], 'dislikes' => (int)$row['dislikes']];
        }
        ob_end_clean();
        echo json_encode(['status' => 'success', 'engagements' => $engagements], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $gameId = (int)($_GET['id'] ?? 0);
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $gameId = (int)($input['game_id'] ?? $gameId);
        $action = strtolower(trim((string)($input['action'] ?? '')));
        if ($gameId < 1) throw new Exception('Invalid or missing game ID.', 400);

        $gameCheck = $db->prepare('SELECT id, public FROM games WHERE id = ? LIMIT 1');
        $gameCheck->execute([$gameId]);
        if (!$gameCheck->fetch(PDO::FETCH_ASSOC)) throw new Exception('Game entity not found.', 404);

        if ($action === 'visit') {
            $visitorUserId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
            $visitStmt = $db->prepare('INSERT INTO game_visits (game_id, visitor_user_id) VALUES (?, ?)');
            $visitStmt->execute([$gameId, $visitorUserId > 0 ? $visitorUserId : null]);
        } elseif ($action === 'vote') {
            $userId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
            if ($userId < 1) throw new Exception('You must be logged in to vote.', 401);
            $vote = strtolower(trim((string)($input['vote'] ?? '')));
            if (!in_array($vote, ['like', 'dislike'], true)) throw new Exception('Invalid vote.', 400);
            $voteStmt = $db->prepare("INSERT INTO game_votes (game_id, user_id, vote) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE vote = VALUES(vote), updated_at = CURRENT_TIMESTAMP");
            $voteStmt->execute([$gameId, $userId, $vote]);
        } else {
            throw new Exception('Invalid engagement action.', 400);
        }
    } elseif ($method !== 'GET') {
        throw new Exception('Method Not Allowed.', 405);
    }

    if ($gameId < 1) throw new Exception('Invalid or missing game ID.', 400);
    $countsStmt = $db->prepare("SELECT
        (SELECT COUNT(*) FROM game_visits WHERE game_id = ?) AS visitors,
        (SELECT COUNT(*) FROM game_votes WHERE game_id = ? AND vote = 'like') AS likes,
        (SELECT COUNT(*) FROM game_votes WHERE game_id = ? AND vote = 'dislike') AS dislikes");
    $countsStmt->execute([$gameId, $gameId, $gameId]);
    $counts = $countsStmt->fetch(PDO::FETCH_ASSOC) ?: ['visitors' => 0, 'likes' => 0, 'dislikes' => 0];

    $viewerVote = null;
    $viewerId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
    if ($viewerId > 0) {
        $viewerVoteStmt = $db->prepare('SELECT vote FROM game_votes WHERE game_id = ? AND user_id = ? LIMIT 1');
        $viewerVoteStmt->execute([$gameId, $viewerId]);
        $viewerVote = $viewerVoteStmt->fetchColumn() ?: null;
    }

    ob_end_clean();
    echo json_encode([
        'status' => 'success',
        'engagement' => [
            'visitors' => (int)$counts['visitors'],
            'likes' => (int)$counts['likes'],
            'dislikes' => (int)$counts['dislikes'],
            'viewer_vote' => $viewerVote
        ]
    ], JSON_UNESCAPED_UNICODE);
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

    $dailyStmt = $db->prepare('SELECT COUNT(*) FROM games WHERE ownerUserId = ? AND created_at >= CURDATE()');
    $dailyStmt->execute([(int)$sessionUserId]);
    if ((int)$dailyStmt->fetchColumn() >= 1) {
        throw new Exception('You can create only one game per day.', 429);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($data['name'])) {
        throw new Exception('Validation Failed: name property is required.', 400);
    }

    $thumbnails = $data['thumbnail_urls'] ?? [];
    if (count($thumbnails) > 8) throw new Exception('Array boundary exceeded: Maximum of 8 media structures allowed.', 400);

    $cleaned_thumbnails = [];
    $imageCount = 0;
    foreach ($thumbnails as $media) {
        if (isset($media['type'], $media['id'])) {
            if (strtolower(trim((string)$media['type'])) === 'image') {
                $imageCount++;
                if ($imageCount > 5) throw new Exception('A maximum of 5 images is allowed per game.', 400);
            }
            $cleaned_thumbnails[] = [
                'type'       => trim((string)$media['type']),
                'id'         => (int)$media['id']
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
        $imageCount = 0;
        foreach ($thumbnails as $media) {
                if (isset($media['type'], $media['id'])) {
                    if (strtolower(trim((string)$media['type'])) === 'image') {
                        $imageCount++;
                        if ($imageCount > 5) throw new Exception('A maximum of 5 images is allowed per game.', 400);
                    }
                    $cleaned_thumbnails[] = [
                        'type'       => trim((string)$media['type']),
                        'id'         => (int)$media['id']
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
    $activeOwnerFilter = "NOT EXISTS (
        SELECT 1 FROM user_bans ban_filter
        WHERE ban_filter.user_id = games.ownerUserId
          AND ban_filter.is_active = 1
          AND (ban_filter.expires_at IS NULL OR ban_filter.expires_at > NOW())
    )";

    // Toplam eşleşen public oyun sayısını hesapla
    $totalStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM games 
        WHERE (public = 1 OR public = '1') AND {$activeOwnerFilter}
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
        WHERE (public = 1 OR public = '1') AND {$activeOwnerFilter}
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
        addDwfAssetId($game);
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
