<?php
/*
|--------------------------------------------------------------------------
| MUTLAK YOL TANIMLAMASI (Çözüm 1)
|--------------------------------------------------------------------------
| Bu sabit, bu dosyanın bulunduğu ana dizini tam yol olarak tutar.
| Örn: /var/www/vhosts/://site.com
*/
define('ROOT_PATH', __DIR__ . '/');

/*
|--------------------------------------------------------------------------
| HTTPS -> HTTP YÖNLENDİRMESİ
|--------------------------------------------------------------------------
*/
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    
    $redirect_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: " . $redirect_url, true, 301);
    exit;
}

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
| ANA SAYFA VE PAGES/HOME YÖNLENDİRMESİ
|--------------------------------------------------------------------------
*/

// Eğer biri ://site.com yazarsa onu ana sayfaya yönlendir
if ($uri === 'index.php') {
    header("Location: /", true, 301);
    exit;
}

// Ana sayfa kuralı veya doğrudan /pages/home çağrısı
if ($uri === '' || $uri === 'pages/home') {
    require ROOT_PATH . 'pages/home.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| OYUN DETAY (games/25485 sistemi)
|--------------------------------------------------------------------------
*/
// İlk parça 'games' ise ve ikinci parça varsa (ve sayıysa)
if (isset($uri_parts[0]) && $uri_parts[0] === 'games' && isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
    $id = $uri_parts[1]; // ID'yi yakaladık!
    require ROOT_PATH . 'games.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| DİĞER SAYFALAR
|--------------------------------------------------------------------------
*/
// Eğer gelen URI doğrudan bir dosya olarak mevcutsa (.php ekleyerek kontrol et)
$file = $uri . '.php';
if (file_exists(ROOT_PATH . $file)) {
    require ROOT_PATH . $file;
    exit;
}

// Alternatif: Eğer pages/ klasörü altındaki bir sayfa çağrılıyorsa (Örn: /hakkimizda -> pages/hakkimizda.php)
$page_file = 'pages/' . $uri . '.php';
if (file_exists(ROOT_PATH . $page_file)) {
    require ROOT_PATH . $page_file;
    exit;
}

// 404 - Sayfa Bulunamadı
http_response_code(404);
echo "404 - Page Not Found";
exit;
