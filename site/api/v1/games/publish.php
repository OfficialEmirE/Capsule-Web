<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function isValidApiKey($apikey, $gameId)
{
    try {
        $config = require __DIR__ . "/../../../config.php";

        $conn = new mysqli(
            $config['db_host'],
            $config['db_user'],
            $config['db_pass'],
            $config['db_name']
        );

        if ($conn->connect_error) {
            throw new Exception("Database connection failed");
        }

        $stmt = $conn->prepare("
            SELECT EXISTS (
                SELECT 1
                FROM games
                WHERE owner_apikey = ?
                AND id = ?
            )
        ");

        $stmt->bind_param("si", $apikey, $gameId);
        $stmt->execute();
        $stmt->bind_result($exists);
        $stmt->fetch();

        return (bool) $exists;

    } catch (Exception $e) {
        http_response_code(500);
        return false;
    }
}

// Sadece POST kabul et
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Only POST allowed");
}

// Parametreler
$id = $_POST["id"] ?? null;
$apikey = $_POST["apikey"] ?? null;

// Kontrol
if ($id === null || $apikey === null) {
    http_response_code(400);
    exit("Missing id or apikey");
}

// Güvenlik: id sadece sayı
if (!ctype_digit($id)) {
    http_response_code(400);
    exit("Invalid id");
}

if (!isValidApiKey($apikey, $id)) {
    http_response_code(403);
    exit("Invalid api key");
}

// Dosya kontrolü
if (!isset($_FILES["file"])) {
    http_response_code(400);
    exit("File not provided");
}

$file = $_FILES["file"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    exit("Upload error");
}

// Hedef klasör
$baseDir = "../../../games/";
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

$targetPath = $baseDir . $id . ".dew";

// Dosyayı taşı
if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
    http_response_code(500);
    exit("Failed to save file");
}

echo "OK";
