<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| 1. HTTP -> HTTPS VE TEMEL REDIRECTS (En Hızlı Başlatma)
|--------------------------------------------------------------------------
*/
// SSL Yönlendirmesini en üste aldık ki veritabanı vs. boşuna yüklenmesin.
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

define('ROOT_PATH', __DIR__ . '/');

/*
|--------------------------------------------------------------------------
| 2. URL PARSING & HIZLI KARA/BEYAZ LİSTE
|--------------------------------------------------------------------------
*/
$raw_uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = strtok($raw_uri, '?'); // explode yerine çok daha hızlı strtok
$uri = strtolower(trim($uri, '/'));

if ($uri === 'index.php') {
    header("Location: /", true, 301);
    exit;
}

// O(1) Karmaşıklığı için Dizi Anahtarı (Key) Mantığı
$ban_whitelist = [
    'api/v1/auth/logout' => true,
    'termsofuse'          => true,
    'privacypolicy'       => true,
    'auth/reset'          => true
];

$blacklist = [
    'users/userinfo' => true,
    'pages/banned'   => true,
    'pages/banned.php' => true
];

// Kara liste kontrolü
if (isset($blacklist[$uri])) {
    render404();
}

/*
|--------------------------------------------------------------------------
| 3. OTURUM VE BAN KONTROLÜ (SESSION CACHE - MAX SPEED)
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_whitelisted = isset($ban_whitelist[$uri]);

if (isset($_SESSION['user_id']) && !$is_whitelisted) {
    // Sitenin yavaşlamasını engellemek için ban bilgisini Session'da saklıyoruz
    if (!isset($_SESSION['is_banned_cached_at']) || (time() - $_SESSION['is_banned_cached_at']) > 60) {
        require_once ROOT_PATH . 'api/config.php';
        try {
            $db = api_db();
            $banCheck = $db->prepare("
                SELECT id FROM user_bans 
                WHERE user_id = ? AND is_active = 1 
                AND (expires_at IS NULL OR expires_at > NOW())
                LIMIT 1
            ");
            $banCheck->execute([$_SESSION['user_id']]);
            
            $_SESSION['is_banned'] = (bool)$banCheck->fetchColumn();
            $_SESSION['is_banned_cached_at'] = time(); // 60 saniyede bir DB'yi kontrol eder
        } catch (Exception $e) {
            $_SESSION['is_banned'] = false;
        }
    }

    if (!empty($_SESSION['is_banned'])) {
        require ROOT_PATH . 'pages/banned.php';
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 4. ROUTER MAPPING & STATIC ROUTES
|--------------------------------------------------------------------------
*/
// Autoload ve Config sadece ihtiyaç olursa çağrılsın
require_once ROOT_PATH . 'api/config.php';
if (file_exists(ROOT_PATH . 'vendor/autoload.php')) {
    require_once ROOT_PATH . 'vendor/autoload.php';
}

// Ana Sayfa Rotaları
if ($uri === '' || $uri === 'pages/home') {
    require ROOT_PATH . 'pages/home.php';
    exit;
}

if ($uri === 'banned') {
    require ROOT_PATH . 'pages/banned.php';
    exit;
}

// API Gateway (O(1) Kontrol)
if (str_starts_with($uri, 'api/')) {
    if (!str_starts_with($uri, 'api/v1')) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'No such file or directory'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    require ROOT_PATH . 'api/v1/index.php';
    exit;
}

// User Page Route
$uri_parts = explode('/', $uri);
if (($uri_parts[0] ?? '') === 'users' && isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
    $id = (int)$uri_parts[1];
    require ROOT_PATH . 'pages/users/userinfo.php';
    exit;
}

// Dinamik Sayfa Yükleme (pages/*.php)
$page_file = ROOT_PATH . 'pages/' . $uri . '.php';
if (file_exists($page_file)) {
    require $page_file;
    exit;
}

/*
|--------------------------------------------------------------------------
| 5. 404 NOT FOUND HANDLER
|--------------------------------------------------------------------------
*/
render404();

function render404(): void {
    http_response_code(404);
    $errorCode = 404;

    if (file_exists(ROOT_PATH . 'error.php')) {
        require ROOT_PATH . 'error.php';
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'No such file or directory'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}