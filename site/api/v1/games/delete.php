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
$gameID = $data["id"] ?? null;
$apikey = $data["apikey"] ?? null;

if ($gameID === null || $apikey === null) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => 'Missing data! Required fields: id, apikey'
    ]);
    ob_end_clean();
    exit;
}

try {
    $config = require __DIR__ . "/../../../config.php";

    // Veritabanı bağlantısı
    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // 1. KULLANICI DOĞRULAMASI: Bu apikey geçerli mi?
    // Bu adım ZORUNLUDUR! Oyunun geçersiz bir apikey ile eklenmesini engeller.
    $stmt_user = $conn->prepare("SELECT apikey FROM users WHERE apikey = ?");
    if (!$stmt_user) {
        throw new Exception("User check prepare failed: " . $conn->error);
    }
    $stmt_user->bind_param("s", $apikey);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows === 0) {
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Invalid or non-existent API Key."
        ]);
        $stmt_user->close();
        $conn->close();
        ob_end_clean();
        exit;
    }

    $stmt_user->close();

    // 2. OYUN DOĞRULAMASI: Bu oyun var mı?
    $stmt_game = $conn->prepare("SELECT id FROM games WHERE id = ?");
    if (!$stmt_game) {
        throw new Exception("Game check prepare failed: " . $conn->error);
    }
    $stmt_game->bind_param("i", $gameID);
    $stmt_game->execute();
    $result_game = $stmt_game->get_result();

    if ($result_game->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Game not found."
        ]);
        $stmt_game->close();
        $conn->close();
        ob_end_clean();
        exit;
    }
    $stmt_game->close();

    // 3. OYUN SİLME
    $stmt_delete = $conn->prepare("DELETE FROM games WHERE id = ?");
    if (!$stmt_delete) {
        throw new Exception("Game delete prepare failed: " . $conn->error);
    }
    $stmt_delete->bind_param("i", $gameID);
    $stmt_delete->execute();
    $stmt_delete->close();
    $conn->close();

    $baseDir = "../../../games/";
    $filePath = $baseDir . $id . ".dew";

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Başarılı cevap
    echo json_encode([
        "status" => "success",
        "message" => "Game deleted successfully"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Hata durumunda diğer çıktıları temizle
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Internal server error: " . $e->getMessage()
    ]);
    exit;
}

// Buffer'ı temizle ve gönder
ob_end_flush();
?>