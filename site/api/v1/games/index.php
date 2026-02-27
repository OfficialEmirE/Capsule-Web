<?php
// Output buffering başlat
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    // Config yükle
    $config = require __DIR__ . "/../../../config.php";

    // Veritabanı bağlantısı
    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Gelen parametreleri al
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $username = $_GET['username'] ?? null;

    $selectColumns = "games.id, games.title, games.image_url, games.description, 
                  games.players, games.visits, games.created_at, games.updated_at, 
                  users.username";

    // LEFT JOIN ile tabloları apikey üzerinden birleştir
    $baseQuery = "SELECT {$selectColumns} 
                  FROM games 
                  LEFT JOIN users ON games.owner_apikey = users.apikey";

    if ($id !== null && $id > 0) {
        // ID'ye göre tek bir oyun getir (Yanında sahibiyle birlikte)
        $stmt = $conn->prepare($baseQuery . " WHERE games.id = ?");
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $game = $result->fetch_assoc();
        $stmt->close();

        if ($game) {
            echo json_encode(["status" => "success", "data" => $game], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Game not found"]);
        }

    } elseif ($username !== null) {
        // KULLANICI ADINA göre oyunları getir
        // Burada users tablosundaki username'e bakıyoruz
        $stmt = $conn->prepare($baseQuery . " WHERE users.username = ?");
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Bir kullanıcının birden fazla oyunu olabilir, bu yüzden döngü (while) kullanıyoruz
        $games = [];
        while ($row = $result->fetch_assoc()) {
            $games[] = $row;
        }
        $stmt->close();

        // Kullanıcı bulunduysa (oyunları varsa)
        if (!empty($games)) {
            echo json_encode(["status" => "success", "data" => $games], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["status" => "error", "message" => "Game not found"]);
        }

    } else {
        http_response_code(200);
        $stmt = $conn->prepare($baseQuery);
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $games = [];
        while ($row = $result->fetch_assoc()) {
            $games[] = $row;
        }
        $stmt->close();

        if (!empty($games)) {
            echo json_encode(["status" => "success", "data" => $games], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["status" => "error", "message" => "Game not found"]);
        }
    }

    $conn->close();

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}

ob_end_flush();