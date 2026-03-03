<?php
// URL'yi güvenli al
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Query string temizle (?a=1)
$uri = explode('?', $uri)[0];

// Baştaki ve sondaki / sil
$uri = trim($uri, '/');

// URL'yi parçalara ayır (Örn: "games/25485" -> ['games', '25485'])
$uri_parts = explode('/', $uri);

/*
|--------------------------------------------------------------------------
| ANA SAYFA
|--------------------------------------------------------------------------
| capsule.net.tr veya /index.php → home.php
*/

// Eğer biri site.com/index.php yazarsa onu ana sayfaya (site.com) yönlendir (SEO için en iyisi budur)
if ($uri === 'index.php') {
    header("Location: /", true, 301);
    exit;
}

// Ana sayfa kuralı
if ($uri === '') {
    require 'home.php';
    exit;
}


/*
|--------------------------------------------------------------------------
| OYUN DETAY (games/25485 sistemi)
|--------------------------------------------------------------------------
*/
// İlk parça 'games' ise ve ikinci parça varsa (ve sayıysa)
if ($uri_parts[0] === 'games' && isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
    $id = $uri_parts[1]; // ID'yi yakaladık!
    require 'games.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| DİĞER SAYFALAR
|--------------------------------------------------------------------------
| /test → test.php
*/
// Eğer uri "hakkimizda" ise ve hakkimizda.php varsa
$file = $uri . '.php';
if (file_exists($file)) {
    require $file;
    exit;
}

// 404
http_response_code(404);
echo "404 - Sayfa Bulunamadı";
exit;
