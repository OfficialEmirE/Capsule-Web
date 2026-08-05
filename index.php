<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__ . '/');

/*
|--------------------------------------------------------------------------
| 1. URL PARSING
|--------------------------------------------------------------------------
*/

$uri = strtolower(
    trim(
        strtok($_SERVER['REQUEST_URI'] ?? '/', '?'),
        '/'
    )
);

if ($uri === 'index.php') {
    header('Location: /', true, 301);
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. BLACKLIST
|--------------------------------------------------------------------------
*/

static $blacklist = [
    'users/userinfo'          => true,
    'pages/banned'            => true,
    'pages/games/gameinfo'    => true,
    'pages/banned.php'        => true,
];

if (isset($blacklist[$uri])) {
    render404();
}

/*
|--------------------------------------------------------------------------
| 3. SESSION
|--------------------------------------------------------------------------
|
| Session'ı sadece oku ve hemen kapat.
| Böylece request boyunca session lock tutulmaz.
|
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'read_and_close' => true,
        'use_strict_mode' => true,
    ]);
}

/*
|--------------------------------------------------------------------------
| 4. MAINTENANCE
|--------------------------------------------------------------------------
*/

$is_maintenance_active = false;

if ($is_maintenance_active) {

    static $maintenance_whitelist = [
        'login'               => true,
        'pages/login'         => true,
        'api/v1/auth/login'   => true,
        'api/v1/auth/logout'  => true,
    ];

    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $allowed_ips = [
        '127.0.0.1' => true,
        '::1'       => true,
    ];

    $is_admin = !empty($_SESSION['is_admin']);

    if (
        !isset($maintenance_whitelist[$uri]) &&
        !isset($allowed_ips[$user_ip]) &&
        !$is_admin
    ) {
        http_response_code(503);
        header('Retry-After: 3600');

        if (str_starts_with($uri, 'api/')) {
            json_error(
                503,
                'System is currently under maintenance. Please try again later.'
            );
        }

        $errorCode = 503;

        if (is_file(ROOT_PATH . 'error.php')) {
            require ROOT_PATH . 'error.php';
        } else {
            json_error(503, 'Service Unavailable');
        }

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 5. BAN CONTROL
|--------------------------------------------------------------------------
|
| ÖNEMLİ:
| Session yerine APCu kullanıyoruz.
|
| APCu yoksa fallback olarak session cache kullanılır.
|
*/

$ban_whitelist = [
    'api/v1/auth/logout' => true,
    'termsofuse'         => true,
    'privacypolicy'      => true,
    'auth/reset'         => true,
];

$user_id = $_SESSION['user_id'] ?? null;

if (
    $user_id !== null &&
    !isset($ban_whitelist[$uri])
) {
    $is_banned = getBanStatus((int)$user_id);

    if ($is_banned) {
        require ROOT_PATH . 'pages/banned.php';
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 6. API
|--------------------------------------------------------------------------
|
| API'yi mümkün olduğunca erken çalıştır.
|
*/

if (str_starts_with($uri, 'api/')) {

    if (!str_starts_with($uri, 'api/v1/')) {
        render404();
    }

    require ROOT_PATH . 'api/v1/index.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| 7. STATIC ROUTES
|--------------------------------------------------------------------------
*/

switch ($uri) {

    case '':
    case 'pages/home':
        require ROOT_PATH . 'pages/home.php';
        exit;

    case 'banned':
        require ROOT_PATH . 'pages/banned.php';
        exit;
}

/*
|--------------------------------------------------------------------------
| 8. USERS / GAMES
|--------------------------------------------------------------------------
*/

$parts = explode('/', $uri, 3);

$section = $parts[0] ?? '';
$id      = $parts[1] ?? null;

if (
    ($section === 'users' || $section === 'games') &&
    $id !== null &&
    ctype_digit($id)
) {
    $id = (int)$id;

    if ($section === 'users') {
        require ROOT_PATH . 'pages/users/userinfo.php';
    } else {
        require ROOT_PATH . 'pages/games/gameinfo.php';
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| 9. DYNAMIC PAGES
|--------------------------------------------------------------------------
|
| Path traversal engelle.
|
*/

if (
    $uri !== '' &&
    !str_contains($uri, '..') &&
    !str_contains($uri, '\\')
) {
    $page_file = ROOT_PATH . 'pages/' . $uri . '.php';

    if (is_file($page_file)) {
        require $page_file;
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 10. 404
|--------------------------------------------------------------------------
*/

render404();

/*
|--------------------------------------------------------------------------
| FUNCTIONS
|--------------------------------------------------------------------------
*/

/**
 * Ban durumunu hızlı şekilde getirir.
 *
 * APCu varsa:
 *   RAM → çok hızlı
 *
 * APCu yoksa:
 *   Session fallback
 */
function getBanStatus(int $userId): bool
{
    $cacheKey = 'user_ban_' . $userId;

    /*
    |--------------------------------------------------------------------------
    | APCu
    |--------------------------------------------------------------------------
    */

    if (
        function_exists('apcu_fetch') &&
        function_exists('apcu_store')
    ) {
        $success = false;

        $cached = apcu_fetch($cacheKey, $success);

        if ($success) {
            return (bool)$cached;
        }

        /*
        |--------------------------------------------------------------------------
        | DB
        |--------------------------------------------------------------------------
        */

        require_once ROOT_PATH . 'api/config.php';

        try {
            $db = api_db();

            $stmt = $db->prepare("
                SELECT 1
                FROM user_bans
                WHERE user_id = ?
                  AND is_active = 1
                  AND (
                      expires_at IS NULL
                      OR expires_at > NOW()
                  )
                LIMIT 1
            ");

            $stmt->execute([$userId]);

            $isBanned = $stmt->fetchColumn() !== false;

            /*
            | 5 dakika RAM cache
            */
            apcu_store(
                $cacheKey,
                $isBanned,
                300
            );

            return $isBanned;

        } catch (Throwable $e) {

            /*
            | DB hata verirse kullanıcıyı otomatik banlı kabul etme.
            */
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | APCu yoksa session fallback
    |--------------------------------------------------------------------------
    */

    $cachedAt = $_SESSION['is_banned_cached_at'] ?? 0;

    if (
        isset($_SESSION['is_banned']) &&
        (time() - $cachedAt) < 300
    ) {
        return (bool)$_SESSION['is_banned'];
    }

    require_once ROOT_PATH . 'api/config.php';

    try {

        $db = api_db();

        $stmt = $db->prepare("
            SELECT 1
            FROM user_bans
            WHERE user_id = ?
              AND is_active = 1
              AND (
                  expires_at IS NULL
                  OR expires_at > NOW()
              )
            LIMIT 1
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchColumn() !== false;

    } catch (Throwable $e) {
        return false;
    }
}

/**
 * JSON error response
 */
function json_error(int $code, string $message): never
{
    http_response_code($code);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        [
            'status'  => 'error',
            'code'    => $code,
            'message' => $message,
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

/**
 * 404
 */
function render404(): never
{
    http_response_code(404);

    $errorCode = 404;

    $errorFile = ROOT_PATH . 'error.php';

    if (is_file($errorFile)) {
        require $errorFile;
        exit;
    }

    json_error(
        404,
        'No such file or directory'
    );
}