<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set("display_errors", "0");

// Fatal error yakalayıcı (JSON yapısının bozulmasını önler)
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_length()) ob_clean();
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Internal Server Error: " . $error['message']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

/**
 * Ana Kullanıcı API Yönlendiricisi
 */
function handleUsersApi(string $action): void {
    try {
        $db = api_db();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database connection error: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    switch (strtolower($action)) {
        case 'info':
            handleInfo($db);
            break;
        case 'list':
            handleUserList($db);
            break;
        case 'avatar':
            handleAvatar($db); 
            break;
        case 'update':
            handleUpdate($db);
            break;
        case 'search':
            handleUserSearch($db);
            break;
        default:
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid endpoint. Use info, list, avatar, update, or search."], JSON_UNESCAPED_UNICODE);
            exit;
    }
}

/**
 * 1. USER INFO ENDPOINT (/api/v1/users/info)
 */
function handleInfo(PDO $db): void {
    $username = trim($_GET['username'] ?? '');
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($username === '' && $userId === 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing username or id"]);
        exit;
    }

    if ($userId > 0) {
        $stmt = $db->prepare("SELECT id, username, avatar, bio, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("SELECT id, username, avatar, bio, created_at FROM users WHERE username = ?");
        $stmt->execute([$username]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }

    echo json_encode(["status" => "success", "user" => $user], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 2. USER LIST ENDPOINT (/api/v1/users/list)
 */
function handleUserList(PDO $db): void {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $totalCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPages = (int)ceil($totalCount / $limit);

    $stmt = $db->prepare("SELECT id, username, avatar FROM users ORDER BY id ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        "status" => "success",
        "users" => $users,
        "pagination" => [
            "current_page" => $page,
            "total_pages" => $totalPages,
            "total_users" => $totalCount
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 3. AVATAR ENDPOINT (/api/v1/users/avatar)
 */
function handleAvatar(PDO $db): void {
    $username = trim($_GET['username'] ?? '');
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($username === '' && $userId === 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing username or id"]);
        exit;
    }

    if ($userId > 0) {
        $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("SELECT avatar FROM users WHERE username = ?");
        $stmt->execute([$username]);
    }

    $avatarColor = $stmt->fetchColumn();

    if ($avatarColor === false) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "avatar" => $avatarColor ?: "#ffffff"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 4. USER UPDATE ENDPOINT (/api/v1/users/update)
 */
function handleUpdate(PDO $db): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

    if (!$userId) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized. Please log in."]);
        exit;
    }

    $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $newUsername = isset($rawInput['username']) ? trim($rawInput['username']) : (isset($_REQUEST['username']) ? trim($_REQUEST['username']) : null);
    $newAvatar   = isset($rawInput['avatar']) ? trim($rawInput['avatar']) : (isset($_REQUEST['avatar']) ? trim($_REQUEST['avatar']) : null);
    $newBio      = isset($rawInput['bio']) ? trim($rawInput['bio']) : (isset($_REQUEST['bio']) ? trim($_REQUEST['bio']) : null);

    if ($newUsername === null && $newAvatar === null && $newBio === null) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Nothing to update. Send username, avatar, or bio."]);
        exit;
    }

    $stmt = $db->prepare("SELECT id, username, avatar, bio FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }

    $updateFields = [];
    $updateParams = [];

    if ($newUsername !== null && $newUsername !== '') {
        if ($user['username'] !== $newUsername) {
            $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $checkStmt->execute([$newUsername, $userId]);
            if ($checkStmt->fetchColumn()) {
                http_response_code(409);
                echo json_encode(["status" => "error", "message" => "This username is already taken"], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $updateFields[] = "username = ?";
            $updateParams[] = $newUsername;
        }
    }

    if ($newAvatar !== null && $newAvatar !== '') {
        if ($user['avatar'] !== $newAvatar) {
            $updateFields[] = "avatar = ?";
            $updateParams[] = $newAvatar;
        }
    }

    if ($newBio !== null) {
        if ($user['bio'] !== $newBio) {
            $updateFields[] = "bio = ?";
            $updateParams[] = $newBio;
        }
    }

    if (empty($updateFields)) {
        echo json_encode(["status" => "success", "message" => "No fields changed, data is already up to date."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $updateParams[] = $userId;
    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
    
    $updateStmt = $db->prepare($sql);
    $updateStmt->execute($updateParams);

    echo json_encode([
        "status" => "success",
        "message" => "User information updated successfully",
        "updated_fields" => array_filter([
            "username" => (isset($newUsername) && $newUsername !== $user['username']) ? $newUsername : null,
            "avatar" => (isset($newAvatar) && $newAvatar !== $user['avatar']) ? $newAvatar : null,
            "bio" => (isset($newBio) && $newBio !== $user['bio']) ? $newBio : null
        ])
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 5. USER SEARCH ENDPOINT (/api/v1/users/search)
 */
function handleUserSearch(PDO $db): void {
    $query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    if ($query === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing search query parameter 'q'"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $searchTerm = '%' . $query . '%';

    // Toplam eşleşen kullanıcı sayısını hesapla
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username LIKE ?");
    $totalStmt->execute([$searchTerm]);
    $totalCount = (int)$totalStmt->fetchColumn();
    $totalPages = (int)ceil($totalCount / $limit);

    // Kullanıcıları getir (ID sıralı)
    $stmt = $db->prepare("
        SELECT id, username, avatar, bio 
        FROM users 
        WHERE username LIKE :query 
        ORDER BY id ASC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        "status" => "success",
        "query" => $query,
        "users" => $users,
        "pagination" => [
            "current_page" => $page,
            "total_pages" => $totalPages,
            "total_users" => $totalCount
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}