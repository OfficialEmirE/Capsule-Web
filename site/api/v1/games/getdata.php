<?php
// games klasörünün yolu
$baseDir = "../../../games/";

try {
    // Config yükle
    $config = require __DIR__ . "/../../../config.php";

    // Veritabanı bağlantısı
    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // id parametresi var mı?
    if (!isset($_GET["id"]) || !isset($_GET["apiKey"])) {
        http_response_code(400);
        exit("Missing id and ApiKey");
    }

    $apikey = $_GET["apiKey"];

    $stmt = $conn->prepare("
    SELECT EXISTS (
        SELECT 1 FROM users WHERE apikey = ?
    )
");
    $stmt->bind_param("s", $apikey);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_row();

    $exists = (bool) $row[0];

    if (!$exists) {
        http_response_code(401);
        exit("Invalid API key");
    }

    // Güvenlik: sadece sayı kabul et
    $id = $_GET["id"];
    if (!ctype_digit($id)) {
        http_response_code(400);
        exit("Invalid id");
    }

    $filePath = $baseDir . $id . ".dew";

    // Dosya var mı?
    if (!file_exists($filePath)) {
        $filePath = $baseDir . "empty.dew";
    }
} catch (Throwable $e) {
    http_response_code(500);
    exit($e->getMessage());
}

// Header'lar
header("Content-Type: application/octet-stream");
header("Content-Length: " . filesize($filePath));
header("Content-Disposition: inline; filename=\"$id.dew\"");
header("Cache-Control: no-cache");

// Dosyayı gönder
readfile($filePath);
exit;
