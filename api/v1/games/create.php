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
$title = $data['title'] ?? null;
$desc = $data['desc'] ?? null;
$img_url = $data['image_url'] ?? null;

// Eksik veri kontrolü
if ($owner_apikey === null || $title === null || $desc === null || $img_url === null) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => 'Missing data! Required fields: apiKey, title, desc, image_url'
    ]);
    ob_end_clean();
    exit;
}

try {
    $config = require __DIR__ . "/../../config.php";

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
    $stmt_user->bind_param("s", $owner_apikey);
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


    // 2. BAŞLIK KONTROLÜ (GÜVENLİ): Oyunun varlığını kontrol et (title)
    $stmt_check = $conn->prepare("SELECT id FROM games WHERE title = ?");
    if (!$stmt_check) {
        throw new Exception("Title check prepare failed: " . $conn->error);
    }
    // Bind_param ile SQL Injection önlenir
    $stmt_check->bind_param("s", $title);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        http_response_code(409);
        echo json_encode([
            "status" => "error",
            "message" => "Game title already exists."
        ]);
        $stmt_check->close();
        $conn->close();
        ob_end_clean();
        exit;
    }
    $stmt_check->close();

    // 3. OYUN EKLEME (INSERT)
    // games tablosunda 'desc' yerine 'description' sütunu kullanmanızı şiddetle tavsiye ederim. 
    // Burada 'desc' yerine 'description' kullandığınızı varsayıyorum.
    // 3. OYUN EKLEME (INSERT)
    // games tablosunda 'desc' yerine 'description' sütunu kullanmanızı şiddetle tavsiye ederim. 
    // Burada 'desc' yerine 'description' kullandığınızı varsayıyorum.
    $stmt_insert = $conn->prepare("INSERT INTO games (title, description, image_url, owner_apikey) VALUES (?, ?, ?, ?)");

    if (!$stmt_insert) {
        throw new Exception("Game insert prepare failed: " . $conn->error);
    }

    // NOT: 'description' yerine 'desc' kullanıyorsanız, yukarıdaki SQL'i düzeltin.
    // apikey artık oyunu ekleyen kullanıcının apikey'idir (owner_apikey).
    $stmt_insert->bind_param("ssss", $title, $desc, $img_url, $owner_apikey);
    $stmt_insert->execute();

    $new_game_id = $stmt_insert->insert_id;

    // Başarılı cevap
    echo json_encode([
        "status" => "success",
        "message" => "Game created successfully",
        "game" => [
            "id" => $new_game_id,
            "title" => $title,
            "description" => $desc,
            "image_url" => $img_url
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $stmt_insert->close();
    $conn->close();

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
