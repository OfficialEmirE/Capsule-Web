<?php
/**
 * Google Cloud / Firebase Storage Signed URL Jeneratörü (Composer Gerektirmez)
 * NOT: FIREBASE_BUCKET_NAME ve SERVICE_ACCOUNT_FILE sabitlerinin 
 * config.php içerisinde tanımlı olduğu varsayılmıştır.
 */

/**
 * Service Account (.json) dosyasını kullanarak bağımsız Signed URL üretir.
 *
 * @param string $objectPath Bucket içindeki dosya yolu (ör: "users/1/12.dwf")
 * @param int $expiresInSeconds URL'nin geçerlilik süresi (saniye cinsinden, varsayılan 3600 = 1 saat)
 * @return string Üretilen geçici indirme URL'i
 */
function generateFirebaseSignedUrl(string $objectPath, int $expiresInSeconds = 3600): string 
{
    if (!defined('SERVICE_ACCOUNT_FILE') || !file_exists(SERVICE_ACCOUNT_FILE)) {
        throw new Exception("Service Account JSON dosyası bulunamadı veya SERVICE_ACCOUNT_FILE sabiti tanımlı değil.");
    }

    if (!defined('FIREBASE_BUCKET_NAME')) {
        throw new Exception("FIREBASE_BUCKET_NAME sabiti tanımlı değil.");
    }

    $saData = json_decode(file_get_contents(SERVICE_ACCOUNT_FILE), true);
    if (!$saData || !isset($saData['client_email'], $saData['private_key'])) {
        throw new Exception("Geçersiz Service Account JSON formatı.");
    }

    $clientEmail = $saData['client_email'];
    $privateKey  = $saData['private_key'];

    // URL Encoding (Google Cloud Signed URL V4 / REST gereksinimi)
    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $objectPath)));
    
    $now = time();
    $expiration = $now + $expiresInSeconds;

    // Google V4 Formatında Tarih Biçimlendirmeleri
    $datestamp = gmdate('Ymd', $now);
    $timestamp = gmdate('Ymd\THis\Z', $now);

    $credentialScope = "{$datestamp}/auto/storage/goog4_request";
    $canonicalHeaders = "host:storage.googleapis.com\n";
    $signedHeaders = "host";

    // Canonical Request Oluşturma
    $canonicalRequest = "GET\n"
        . "/{$encodedPath}\n"
        . "X-Goog-Algorithm=GOOG4-RSA-SHA256&"
        . "X-Goog-Credential=" . rawurlencode("{$clientEmail}/{$credentialScope}") . "&"
        . "X-Goog-Date={$timestamp}&"
        . "X-Goog-Expires={$expiresInSeconds}&"
        . "X-Goog-SignedHeaders={$signedHeaders}\n"
        . "{$canonicalHeaders}\n"
        . "{$signedHeaders}\n"
        . "UNSIGNED-PAYLOAD";

    // String to Sign Oluşturma
    $stringToSign = "GOOG4-RSA-SHA256\n"
        . "{$timestamp}\n"
        . "{$credentialScope}\n"
        . hash('sha256', $canonicalRequest);

    // OpenSSL ile Private Key kullanarak İmzalanması
    $binarySignature = '';
    $success = openssl_sign($stringToSign, $binarySignature, $privateKey, 'SHA256');

    if (!$success) {
        throw new Exception("OpenSSL imzalama hatası: " . openssl_error_string());
    }

    $signatureHex = bin2hex($binarySignature);

    // Final Signed URL
    return "https://storage.googleapis.com/" . FIREBASE_BUCKET_NAME . "/{$encodedPath}?"
        . "X-Goog-Algorithm=GOOG4-RSA-SHA256&"
        . "X-Goog-Credential=" . rawurlencode("{$clientEmail}/{$credentialScope}") . "&"
        . "X-Goog-Date={$timestamp}&"
        . "X-Goog-Expires={$expiresInSeconds}&"
        . "X-Goog-SignedHeaders={$signedHeaders}&"
        . "X-Goog-Signature={$signatureHex}";
}

/**
 * Asset yanıt payload'ını oluşturan fonksiyon
 */
function buildAssetInfoPayload(int $creatorId, array $asset): array
{
    $id   = (int)($asset['id'] ?? 0);
    $type = (string)($asset['type'] ?? 'image');

    $payload = [
        'id'   => $id,
        'type' => $type
    ];

    if ($type === 'video') {
        $ytId = $asset['youtube_id'] ?? '';
        $payload['url']       = "https://www.youtube.com/watch?v={$ytId}";
        $payload['embed_url'] = "https://www.youtube.com/embed/{$ytId}";
        $payload['is_temporary'] = false;
    } else {
        // Storage içindeki dosya yolu (örn: users/12/45.dwf veya users/12/45.jpg)
        $extension  = ($type === 'dwf') ? 'dwf' : 'jpg';
        $objectPath = "users/{$creatorId}/{$id}.{$extension}";

        try {
            // 1 saat geçerli Signed URL üret (3600 sn)
            $payload['url']          = generateFirebaseSignedUrl($objectPath, 3600);
            $payload['is_temporary'] = true;
        } catch (Exception $e) {
            error_log("Signed URL Error: " . $e->getMessage());
            $payload['url']          = null;
            $payload['is_temporary'] = false;
        }
    }

    return $payload;
}
/**
 * Router (index.php) tarafından çağrılan ana Asset API işleyicisi
 *
 * @param string $action URL'den gelen eylem (ör: "info", "get" veya boş)
 */
function handleAssetsApi(string $action = ''): void
{
    // Veritabanı bağlantısı ve global değişkenler
    global $pdo;

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    try {
        switch ($method) {
            case 'GET':
                // Örnek kullanım: GET /api/v1/assets/123 veya GET /api/v1/assets?id=123
                $assetId = (int)($action !== '' ? $action : ($_GET['id'] ?? 0));

                if ($assetId <= 0) {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'Geçersiz veya eksik Asset ID.'], JSON_UNESCAPED_UNICODE);
                    return;
                }

                // Veritabanından asset bilgisini çek
                $stmt = $pdo->prepare("SELECT * FROM assets WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $assetId]);
                $asset = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$asset) {
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Asset bulunamadı.'], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $creatorId = (int)($asset['user_id'] ?? $asset['creator_id'] ?? 0);
                
                // Senin buildAssetInfoPayload fonksiyonunla Signed URL'i üretip yanıtı hazırlıyoruz
                $responseData = buildAssetInfoPayload($creatorId, $asset);

                echo json_encode([
                    'status' => 'success',
                    'data'   => $responseData
                ], JSON_UNESCAPED_UNICODE);
                break;

            default:
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Method not allowed.'], JSON_UNESCAPED_UNICODE);
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Sunucu hatası: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}