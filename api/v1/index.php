<?php
declare(strict_types=1);

// Config dosyasını bir üst dizinden çekiyoruz
require_once __DIR__ . '/../config.php';

// Composer paketleri varsa dahil edilsin
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

// URL'i bu klasöre göre anlamlandırmak için parçalayalım
$apiUri = $_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST' 
    ? ($_SERVER['REQUEST_URI'] ?? '/') 
    : '/';

$apiUri = explode('?', $apiUri)[0];
$apiUri = trim($apiUri, '/');
$apiParts = explode('/', $apiUri);

// $apiParts yapısı normalde şöyledir: ['api', 'v1', 'games', 'list']

// Eğer istek tam olarak /api/v1 ise (yani 2. indisten sonrası yoksa) Dökümantasyonu aç
if (!isset($apiParts[2]) || $apiParts[2] === '') {
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    
    $jsonPath = __DIR__ . '/swagger.json';
    if (!file_exists($jsonPath)) {
        die("Hata: 'api/v1/swagger.json' dosyası bulunamadı!");
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

switch ($module) {
    case 'games':
        $apiAction = $apiParts[3] ?? '';
        
        // Aynı klasördeki games.php kütüphanesini yükle ve çalıştır
        require_once __DIR__ . '/games.php';
        handleGamesApi($apiAction);
        exit;

    // İleride buraya yeni modüller eklemek oyun oyuncağı:
    // case 'users':
    //     require_once __DIR__ . '/users.php';
    //     handleUsersApi($apiParts[3] ?? '');
    //     exit;

    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'API modülü bulunamadı.'], JSON_UNESCAPED_UNICODE);
        exit;
}