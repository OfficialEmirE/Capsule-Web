<?php
declare(strict_types=1);

// Tarayıcıya içeriğin bir web sayfası değil, JSON olduğunu bildiriyoruz
header('Content-Type: application/json; charset=utf-8');

// Gelen durum kodunu al (İndex.php'den gelen $errorCode varsa onu kullan, yoksa HTTP durum kodunu al)
$errorCode = $errorCode ?? (int)($_GET['code'] ?? http_response_code());
if ($errorCode === 0 || $errorCode === 200) {
    $errorCode = 404; 
}

// HTTP durum kodunu tarayıcı seviyesinde de ayarla
http_response_code($errorCode);

// Hata mesajlarını JSON formatına uygun olacak şekilde eşleştiriyoruz
$errorMessages = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'No such file or directory',
    500 => 'Internal Server Error',
    503 => 'Service Unavailable'
];

$message = $errorMessages[$errorCode] ?? 'Unknown Error';

// Çıktıyı JSON olarak bas ve çalışmayı bitir
echo json_encode([
    'status' => 'error',
    'code'   => $errorCode,
    'message' => $message
], JSON_UNESCAPED_UNICODE);
exit;