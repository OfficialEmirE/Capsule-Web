<?php
declare(strict_types=1);

// Eğer ana index.php'den ROOT_PATH tanımlanmadıysa mutlak yolu kendimiz hesaplayalım
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2) . '/');
}

// ÇIKTI TAMPONU BAŞLANGICI: Dandik header ve buffer silme hatalarını kökten engeller
ob_start();

// Config dosyasını mutlak yolla güvenli şekilde çekiyoruz
require_once ROOT_PATH . 'api/config.php';

// Composer paketleri varsa dahil edilsin
if (file_exists(ROOT_PATH . 'vendor/autoload.php')) {
    require_once ROOT_PATH . 'vendor/autoload.php';
}

// URL'i anlamlandırmak için parçalayalım
$apiUri = $_SERVER['REQUEST_URI'] ?? '/';
$apiUri = explode('?', $apiUri)[0];
$apiUri = trim($apiUri, '/');
$apiParts = explode('/', $apiUri);

// $apiParts yapısı: ['api', 'v1', 'games', 'list'] şeklindedir.

/*
|--------------------------------------------------------------------------
| SWAGGER UI DÖKÜMANTASYON SİSTEMİ
|--------------------------------------------------------------------------
| Eğer istek tam olarak /api/v1 ise veya arkasından modül gelmediyse dökümantasyonu aç
*/
if (!isset($apiParts[2]) || trim($apiParts[2]) === '') {
    
    // Güvenli tampon temizliği: Sadece açık bir tampon varsa siler
    if (ob_get_length() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: text/html; charset=utf-8');
    
    $jsonPath = __DIR__ . '/swagger.json';
    if (!file_exists($jsonPath)) {
        die("Error: 'api/v1/swagger.json' file could not be found!");
    }
    
    $jsonRaw = file_get_contents($jsonPath);
    $jsonData = json_decode($jsonRaw, true);
    $jsonSpec = json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Capsule Web API - Documentation</title>
        <meta name="robots" content="noindex">
        <link rel="icon" type="image/x-icon" href="/assets/images/studio.ico">
        <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
        <style>body { margin:0; background: #fafafa; }</style>
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
        <script>
            window.onload = () => {
                window.ui = SwaggerUIBundle({
                    spec: <?= $jsonSpec ?>, 
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [SwaggerUIBundle.presets.apis],
                    layout: "BaseLayout"
                });
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}

/*
|--------------------------------------------------------------------------
| SUB-ROUTER: MODÜL YÖNLENDİRMELERİ
|--------------------------------------------------------------------------
*/
$module = strtolower($apiParts[2]);

// API yanıtları için standart header tanımlaması
header('Content-Type: application/json; charset=utf-8');

switch ($module) {
    case 'games':
        $apiAction = $apiParts[3] ?? '';
        
        // Aynı klasördeki games.php kütüphanesini ROOT_PATH ile yükle
        require_once ROOT_PATH . 'api/v1/games.php';
        handleGamesApi($apiAction);
        exit;
    case 'auth':
        $apiAction = $apiParts[3] ?? '';
        
        // Aynı klasördeki auth.php kütüphanesini ROOT_PATH ile yükle
        require_once ROOT_PATH . 'api/v1/auth.php';
        handleAuthApi($apiAction);
        exit;
    case 'users':
        $apiAction = $apiParts[3] ?? '';
        
        // Aynı klasördeki users.php kütüphanesini ROOT_PATH ile yükle
        require_once ROOT_PATH . 'api/v1/users.php';
        handleUsersApi($apiAction);
        exit;
    case 'reports':
    case 'reporter':
        $apiAction = $apiParts[3] ?? '';
        
        // api/v1/reports.php dosyasını dahil ediyoruz
        require_once ROOT_PATH . 'api/v1/reporter.php';
        handleReporterApi($apiAction);
        exit;
    case 'assets':
    case 'asset':
        $apiAction = $apiParts[3] ?? '';
        require_once ROOT_PATH . 'api/v1/assets.php';
        handleAssetsApi($apiAction);
        exit;
    default:
        if (ob_get_length() > 0) {
            ob_end_clean();
        }
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'API module not found.'], JSON_UNESCAPED_UNICODE);
        exit;
}