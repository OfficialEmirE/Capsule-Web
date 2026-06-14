<?php
// register.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");

function uuid_v4()
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return sprintf(
        '%02x%02x%02x%02x-%02x%02x-%02x%02x-%02x%02x-%02x%02x%02x%02x%02x%02x',
        ord($data[0]),
        ord($data[1]),
        ord($data[2]),
        ord($data[3]),
        ord($data[4]),
        ord($data[5]),
        ord($data[6]),
        ord($data[7]),
        ord($data[8]),
        ord($data[9]),
        ord($data[10]),
        ord($data[11]),
        ord($data[12]),
        ord($data[13]),
        ord($data[14]),
        ord($data[15])
    );
}

try {
    $config = require __DIR__ . "/../../config.php";

    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // GET param'ları
    $username = isset($_GET['username']) ? trim($_GET['username']) : '';
    $password = isset($_GET['password']) ? $_GET['password'] : '';
    $avatar = '{"color":"#ffffff","faceURL":"http://capsule.net.tr/api/v1/avatar/avatarurl.php?type=face","avatars":[]}';

    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing username or password (use ?username=...&password=...)"]);
        exit;
    }

    // Kullanıcı var mı kontrol et
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$check)
        throw new Exception("SQL prepare failed: " . $conn->error);
    $check->bind_param("s", $username);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Username already exists"]);
        exit;
    }
    $check->close();

    // Güvenli hash oluştur
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $date = date("Y-m-d");
    $apikey = uuid_v4();

    // Ekle
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, avatar, creation_date, apikey) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sssss", $username, $passwordHash, $avatar, $date, $apikey);
    $stmt->execute();

    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully",
        "user" => [
            "id" => $stmt->insert_id,
            "username" => $username,
            "avatar" => $avatar,
            "creation_date" => $date,
            "apikey" => $apikey
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}

ob_end_flush();
