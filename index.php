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
| URL PARSING
|--------------------------------------------------------------------------
*/
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = explode('?', $uri)[0];
$uri = trim($uri, '/');
$uri_parts = explode('/', $uri);

/*
|--------------------------------------------------------------------------
| GLOBAL VARIABLES INITIALIZATION
|--------------------------------------------------------------------------
| Tanımsız değişken (Undefined variable) hatalarını önlemek için router genelinde
| kullanılan bayrakları (flags) varsayılan olarak false çekiyoruz.
*/
$is_whitelisted = false;
$is_blocked = false;

/*
|--------------------------------------------------------------------------
| AUTOMATIC BAN CONTROL & ROUTING (BEYAZ LİSTE MEKANİZMASI)
|--------------------------------------------------------------------------
*/

// Banlı kullanıcının kesinlikle erişebilmesi gereken rotalar
$ban_whitelist = [
    'api/v1/auth/logout',
    'termsofuse',
    'privacypolicy',
    'auth/reset'    // Eğer ileride bunu kullanırsan diye yedek
];

// Gelen URI'yi temizle ve küçük harfe zorla
$current_uri_clean = strtolower(trim($uri, '/'));

$is_whitelisted = false;
foreach ($ban_whitelist as $white_route) {
    $white_route_clean = strtolower(trim($white_route, '/'));
    
    // Tam eşleşme veya alt dizin kontrolü
    if ($current_uri_clean === $white_route_clean || str_starts_with($current_uri_clean, $white_route_clean . '/')) {
        $is_whitelisted = true;
        break;
    }
}

// Kullanıcı giriş yapmışsa ve whitelist'te DEĞİLSE ban kontrolünü çalıştır
if (isset($_SESSION['user_id']) && !$is_whitelisted) {
    try {
        $db = api_db();
        $banCheck = $db->prepare("
            SELECT id FROM user_bans 
            WHERE user_id = ? AND is_active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
            LIMIT 1
        ");
        $banCheck->execute([$_SESSION['user_id']]);
        
        if ($banCheck->fetch()) {
            // Eğer banlıysa ve normal bir sayfa istiyorsa banned.php'ye yönlendir
            require ROOT_PATH . 'pages/banned.php';
            exit;
        }
    } catch (Exception $e) {
        // DB hatası durumunda çökme
    }
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

// Eğer banlı değilse ve doğrudan /banned sayfasına erişmek istiyorsa burası yakalar
if ($uri === 'banned') {
    require ROOT_PATH . 'pages/banned.php';
    exit;
}

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
| pages/banned.php dosyasına doğrudan URL manipülasyonu ile erişimi engellemek 
| için listeye ekledik.
*/
$blacklist = [
    'users/userinfo',
    'pages/banned',
];

// Gelen URI'yi hem doğrudan, hem de sonuna .php ekleyerek ya da çıkartarak temiz bir şekilde kontrol edelim
$clean_uri = strtolower($uri);
$uri_with_ext = str_ends_with($clean_uri, '.php') ? $clean_uri : $clean_uri . '.php';
$uri_no_ext = str_ends_with($clean_uri, '.php') ? substr($clean_uri, 0, -4) : $clean_uri;

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
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'No such file or directory'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
exit;