<?php
// login.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");

try {
    $config = require __DIR__ . "/../../config.php";

    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // GET param'ları
    $username = isset($_GET['username']) ? trim($_GET['username']) : '';
    $password = isset($_GET['password']) ? $_GET['password'] : '';

    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing username or password (use ?username=...&password=...)"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, password_hash, avatar, creation_date, apikey FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
        exit;
    }

    // Eğer apikey yoksa oluşturup kaydet
    $apikey = $user['apikey'] ?? null;
    if (empty($apikey)) {
        /*$apikey = uuid_v4();
        //$upd = $conn->prepare("UPDATE users SET apikey = ? WHERE id = ?");
        if ($upd) {
            $upd->bind_param("si", $apikey, $user['id']);
            $upd->execute();
            $upd->close();
        }*/
        echo json_encode(["status" => "error", "message" => "API key is missing, please contact support."]);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "user" => [
            "id" => $user['id'],
            "username" => $username,
            "avatar" => $user['avatar'],
            "creation_date" => $user['creation_date'],
            "apikey" => $apikey
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}

ob_end_flush();
