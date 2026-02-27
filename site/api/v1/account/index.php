<?php
// Output buffering başlat - hataları yakalamak için
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");

try {
    // Config yükle
    $config = require __DIR__ . "/../../../config.php";
    
    // Veritabanı bağlantısı
    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    // Hassas alanları tanımla
    $sensitive_fields = ['api_key', 'password_hash'];

    if ($id !== null && $id > 0) {
        // Tek kullanıcı için tüm alanları seç
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Hassas alanları kaldır
            foreach ($sensitive_fields as $field) {
                unset($user[$field]);
            }
            echo json_encode(["status"=>"success","data"=>$user], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(["status"=>"error","message"=>"User not found"]);
        }
    } else {
        // Tüm kullanıcılar için hassas alanları çıkartarak seç
        $fields_to_select = [
            'id', 'username', 'avatar', 'creation_date',
            // api_key ve password_hash burada YOK
        ];
        
        $field_list = implode(', ', $fields_to_select);
        $result = $conn->query("SELECT $field_list FROM users");
        
        if ($result === false) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(["status"=>"success","data"=>$users], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    $conn->close();

} catch (Exception $e) {
    // Buffer'ı temizle
    ob_end_clean();
    http_response_code(500);
    echo json_encode(["status"=>"error", "message"=>$e->getMessage()]);
    exit;
}

// Buffer'ı temizle ve gönder
ob_end_flush();