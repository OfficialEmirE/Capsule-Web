<?php
ob_start();
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors", 0);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        // Fatal error caught
        // Ensure buffer is clean before sending JSON
        if (ob_get_length())
            ob_clean();

        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "status" => "error",
            "message" => "Internal Server Error: " . $error['message']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});


try {
    // Config yükle
    $config = require __DIR__ . "/../../config.php";

    // Veritabanı bağlantısı
    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // GET parametreleri
    $type = $_GET['type'] ?? '';
    $username = trim($_GET['username'] ?? '');
    $apikey = $_GET['apikey'] ?? '';
    $data = $_GET['data'] ?? '';

    if ($type === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing type"]);
        exit;
    }

    // -------------------------
    // GET (username → avatar)
    // -------------------------
    if ($type === 'get') {

        if ($username === '') {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing username"]);
            exit;
        }

        $stmt = $conn->prepare("SELECT avatar FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "User not found: " . $username . ""]);
            exit;
        }

        echo json_encode([
            "status" => "success",
            "avatar" => $user['avatar']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    }

    // -------------------------
    // SET (apikey → avatar değiştir)
    // -------------------------
    if ($type === 'set') {

        if ($apikey === '') {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing apikey"]);
            exit;
        }

        if ($data === '') {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing data"]);
            exit;
        }

        // API key doğrulama
        $stmt = $conn->prepare("SELECT username FROM users WHERE apikey = ?");
        $stmt->bind_param("s", $apikey);
        $stmt->execute();
        $res = $stmt->get_result();
        $owner = $res->fetch_assoc();
        $stmt->close();

        if (!$owner) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Invalid API key"]);
            exit;
        }

        // Avatar güncelle
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE apikey = ?");
        $stmt->bind_param("ss", $data, $apikey);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            "status" => "success",
            "message" => "Avatar updated successfully"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        ob_end_flush();
        exit;
    }

    // Geçersiz type
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid type (use get/set)"]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    ob_end_clean();
    exit;
}


