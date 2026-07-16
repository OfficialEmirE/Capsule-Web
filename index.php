<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| MUTLAK YOL TANIMLAMASI
|--------------------------------------------------------------------------
| Bu sabit, bu dosyanın bulunduğu ana dizini tam yol olarak tutar.
| Dosya yüklemelerinde ve require/include işlemlerinde hata payını sıfırlar.
*/
define('ROOT_PATH', __DIR__ . '/');

// Bağımsız çalışmalarda veritabanı fonksiyonunun kaybolmaması için config'i bağlıyoruz
require_once ROOT_PATH . 'api/config.php';

// Composer paketlerini otomatik yüklemek için:
if (file_exists(ROOT_PATH . 'vendor/autoload.php')) {
    require_once ROOT_PATH . 'vendor/autoload.php';
}

/*
|--------------------------------------------------------------------------
| HTTPS -> HTTP YÖNLENDİRMESİ
|--------------------------------------------------------------------------
| Projenin SSL (HTTPS) yerine HTTP protokolü üzerinden çalışmasını zorlar.
*/
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    
    $redirect_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: " . $redirect_url, true, 301);
    exit;
}

/*
|--------------------------------------------------------------------------
| MAINTENANCE MODE
|--------------------------------------------------------------------------

if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true) {
    http_response_code(503);
    header('Retry-After: 3600');
    $errorCode = 503;
    require ROOT_PATH . 'error.php';
    exit;
}
*/
/*
|--------------------------------------------------------------------------
| URL PARSING
|--------------------------------------------------------------------------
*/
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = explode('?', $uri)[0];
$uri = trim($uri, '/');
$uri_parts = explode('/', $uri);

/*
|--------------------------------------------------------------------------
| HOME & STATIC PAGES YÖNLENDİRMESİ
|--------------------------------------------------------------------------
*/
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
if ($uri === 'Browse') {
    require ROOT_PATH . 'browse.php';
    exit;
}

if ($uri === 'Develop') {
    require ROOT_PATH . 'develop.php';
    exit;
}

if ($uri === 'login') {
    $authMode = 'login';
    require ROOT_PATH . 'auth.php';
    exit;
}

if ($uri === 'register') {
    $authMode = 'register';
    require ROOT_PATH . 'auth.php';
    exit;
}

if ($uri === 'search') {
    require ROOT_PATH . 'search.php';
    exit;
}
*/
/*
|--------------------------------------------------------------------------
| ERROR PAGES
|--------------------------------------------------------------------------

if (in_array($uri, ['400', '401', '403', '404', '500', '503'], true)) {
    $errorCode = (int) $uri;
    require ROOT_PATH . 'error.php';
    exit;
}
*/
/*
|--------------------------------------------------------------------------
| INFO PAGES (Main & Sub-pages)
|--------------------------------------------------------------------------

if (isset($uri_parts[0]) && strtolower($uri_parts[0]) === 'info') {
    
    $subPage = isset($uri_parts[1]) ? strtolower($uri_parts[1]) : 'main'; 

    switch ($subPage) {
        case 'main':
            $pageType = 'info_main';
            $pageTitle = 'Home';
            $pageContent = 'Welcome to the Info section of Capsule! Here you can find important information about our platform, including our Privacy Policy, Terms of Service, and details about our company. Please use the menu on the left to navigate through the different pages.';
            break;

        case 'about':
            $pageType = 'about';
            $pageTitle = 'About Us';
            $pageContent = 'Welcome to the About Us page. Here you can find details about our company, our mission, and the team behind Capsule.';
            break;

        case 'termsofservice':
            $pageType = 'terms';
            $pageTitle = 'Terms of Service';
            $pageContent = 'Welcome to the Terms of Service page. Please read these terms carefully before using our platform to understand your rights and responsibilities.';
            break;

        case 'privacy':
            $pageType = 'privacy';
            $pageTitle = 'Privacy Policy';
            $pageContent = 'Welcome to the Privacy Policy page. Here we explain how we collect, use, protect, and handle your personal data across Capsule.';
            break;

        case 'sourcecode':
            $pageType = 'sourcecode';
            $pageTitle = 'Source Codes';
            $pageContent = 'Welcome to the Source Codes page. Capsule believes in transparency; you can review our open-source repositories and development guidelines here.';
            break;
    }

    if (isset($pageType)) {
        require ROOT_PATH . 'info.php';
        exit;
    }
}
*/
/*
|--------------------------------------------------------------------------
| API V1 GATEWAY (Direkt 404 Kuralı Dahil)
|--------------------------------------------------------------------------
*/
if (isset($uri_parts[0]) && $uri_parts[0] === 'api') {
    
    // Eğer /api/v1 yapısı tam kurulmadıysa (v1 eksikse veya alt indisler yoksa) direkt 404 bas
    if (!isset($uri_parts[1]) || $uri_parts[1] !== 'v1') {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'No such file or directory'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // /api/v1/... ile başlayan tüm geçerli istekleri alt index'e pasla
    require ROOT_PATH . 'api/v1/index.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| OYUN DETAY (games/25485 sistemi)
|--------------------------------------------------------------------------

if (isset($uri_parts[0]) && $uri_parts[0] === 'games' && isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
    $id = $uri_parts[1]; // ID'yi yakaladık!
    require ROOT_PATH . 'games.php';
    exit;
}
*/
/*
|--------------------------------------------------------------------------
| USER PAGE
|--------------------------------------------------------------------------
*/
if (isset($uri_parts[0]) && $uri_parts[0] === 'users' && isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
    $id = (int)$uri_parts[1]; // ID'yi yakaladık ve güvenli olması için integer'a zorladık
    require ROOT_PATH . '/pages/users/userinfo.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| ROUTE BLACKLIST (KARA LİSTE) KONTROLÜ
|--------------------------------------------------------------------------
*/
$blacklist = [
    'users/userinfo',
];

// Gelen URI'yi hem doğrudan, hem de sonuna .php ekleyerek ya da çıkartarak temiz bir şekilde kontrol edelim
$clean_uri = strtolower($uri);
$uri_with_ext = str_ends_with($clean_uri, '.php') ? $clean_uri : $clean_uri . '.php';
$uri_no_ext = str_ends_with($clean_uri, '.php') ? substr($clean_uri, 0, -4) : $clean_uri;

$is_blocked = false;
foreach ($blacklist as $blocked_route) {
    $blocked_route_clean = strtolower(trim($blocked_route, '/'));
    if ($clean_uri === $blocked_route_clean || $uri_with_ext === $blocked_route_clean || $uri_no_ext === $blocked_route_clean) {
        $is_blocked = true;
        break;
    }
}

if ($is_blocked) {
    http_response_code(404);
    $errorCode = 404;

    if (file_exists(ROOT_PATH . 'error.php')) {
        require ROOT_PATH . 'error.php';
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'No such file or directory'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
// Alternatif: pages/ klasörü altında bir sayfa çağrılıyorsa (Örn: /hakkimizda -> pages/hakkimizda.php)
$page_file = 'pages/' . $uri . '.php';
if (file_exists(ROOT_PATH . $page_file)) {
    require ROOT_PATH . $page_file;
    exit;
}

/*
|--------------------------------------------------------------------------
| 404 NOT FOUND
|--------------------------------------------------------------------------
*/
http_response_code(404);
$errorCode = 404;

if (file_exists(ROOT_PATH . 'error.php')) {
    require ROOT_PATH . 'error.php';
} else {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'No such file or directory'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
exit;