<?php
// Output buffering başlat
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");

$requestMethod = $_SERVER["REQUEST_METHOD"];
if ($requestMethod !== 'POST') {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Only POST requests are allowed."
    ]);
    exit;
}

// Gelen JSON'ı al ve parse et
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data === null) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON"
    ]);
    ob_end_clean();
    exit;
}

// Gerekli verileri al
$owner_apikey = $data['apiKey'] ?? null; // Oyunu ekleyen kullanıcının anahtarı

// Eksik veri kontrolü
if ($owner_apikey === null) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => 'Missing data! Required fields: apiKey'
    ]);
    ob_end_clean();
    exit;
}

try {
    $config = require __DIR__ . "/../../../config.php";

    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, avatar, creation_date, apikey FROM users WHERE apikey = ?");
    if (!$stmt) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $owner_apikey);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
        exit;
    }

    // Eğer apikey yoksa oluşturup kaydet
    $apikey = $user['apikey'] ?? null;
    if (empty($apikey)) {
        echo json_encode(["status" => "error", "message" => "API key is missing, please contact support."]);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Account Valid!",
        "user" => [
            "id" => $user['id'],
            "username" => $username,
            "avatar" => $user['avatar'],
            "creation_date" => $user['creation_date']
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