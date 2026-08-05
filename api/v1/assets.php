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
        throw new Exception('Service account JSON file was not found or SERVICE_ACCOUNT_FILE is not configured.');
    }

    if (!defined('FIREBASE_BUCKET_NAME')) {
        throw new Exception("FIREBASE_BUCKET_NAME sabiti tanımlı değil.");
    }

    $saData = json_decode(file_get_contents(SERVICE_ACCOUNT_FILE), true);
    if (!$saData || !isset($saData['client_email'], $saData['private_key'])) {
        throw new Exception('Invalid service account JSON format.');
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
    } elseif ($type === 'image') {
        $objectPath = "users/{$creatorId}/{$id}";
        if (assetStorageIsLocal()) {
            $payload['url'] = localAssetUrl($creatorId, $id);
            $payload['is_temporary'] = false;
        } else {
            $encodedPath = rawurlencode($objectPath);
            $payload['url'] = "https://firebasestorage.googleapis.com/v0/b/" . FIREBASE_BUCKET_NAME . "/o/{$encodedPath}?alt=media";
            $payload['is_temporary'] = false;
        }
    } else {
        // Storage içindeki dosya yolu (örn: users/12/45.dwf veya users/12/45.jpg)
        $objectPath = "users/{$creatorId}/{$id}";

        try {
            if (assetStorageIsLocal()) {
                $payload['url'] = localAssetUrl($creatorId, $id);
                $payload['is_temporary'] = false;
                return $payload;
            }

            // 1 saat geçerli Signed URL üret (3600 sn)
            $payload['url'] = function_exists('getFirebaseSignedUrl')
                ? getFirebaseSignedUrl($objectPath, 3600)
                : generateFirebaseSignedUrl($objectPath, 3600);
            $payload['is_temporary'] = true;
        } catch (Exception $e) {
            error_log("Signed URL Error: " . $e->getMessage());
            $payload['url']          = null;
            $payload['is_temporary'] = false;
        }
    }

    return $payload;
}

function assetStorageIsLocal(): bool
{
    return !defined('ASSET_STORAGE_DRIVER') || ASSET_STORAGE_DRIVER !== 'firebase';
}

function localAssetDirectory(int $creatorId): string
{
    return ROOT_PATH . 'cdn' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $creatorId;
}

function localAssetFile(int $creatorId, int $assetId): ?string
{
    $directory = localAssetDirectory($creatorId);
    $matches = glob($directory . DIRECTORY_SEPARATOR . $assetId . '.*') ?: [];
    return $matches[0] ?? null;
}

function localAssetUrl(int $creatorId, int $assetId): ?string
{
    $file = localAssetFile($creatorId, $assetId);
    if ($file === null) return null;

    $relative = 'cdn/users/' . $creatorId . '/' . basename($file);
    return '/' . implode('/', array_map('rawurlencode', explode('/', $relative)));
}
/**
 * Router (index.php) tarafından çağrılan ana Asset API işleyicisi
 *
 * @param string $action URL'den gelen eylem (ör: "info", "get" veya boş)
 */
function handleAssetUpload(): void
{
    $sessionUserId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
    if (!$sessionUserId) {
        throw new Exception('Unauthorized.', 401);
    }

    $type = strtolower(trim((string)($_POST['type'] ?? '')));
    if (!in_array($type, ['image', 'dwf', 'video'], true)) {
        throw new Exception('type must be image, dwf, or video.', 400);
    }

    if ($type === 'video') {
        $videoUrl = trim((string)($_POST['url'] ?? ''));
        $youtubeId = extractYoutubeId($videoUrl);
        if ($youtubeId === null) {
            throw new Exception('A valid YouTube URL is required.', 400);
        }

        $db = api_db();
        $existingStmt = $db->prepare('SELECT id FROM assets WHERE type = ? AND youtube_id = ? LIMIT 1');
        $existingStmt->execute(['video', $youtubeId]);
        $existingAssetId = $existingStmt->fetchColumn();

        if ($existingAssetId !== false) {
            echo json_encode([
                'status' => 'success',
                'asset' => ['id' => (int)$existingAssetId, 'type' => 'video', 'existing' => true]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $stmt = $db->prepare('INSERT INTO assets (user_id, type, youtube_id) VALUES (?, ?, ?)');
        $stmt->execute([(int)$sessionUserId, 'video', $youtubeId]);
        echo json_encode([
            'status' => 'success',
            'asset' => ['id' => (int)$db->lastInsertId(), 'type' => 'video']
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return;
    }

    if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        throw new Exception('A file field is required.', 400);
    }

    $file = $_FILES['file'];
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed.', 400);
    }
    if ((int)$file['size'] >= 5 * 1024 * 1024) {
        throw new Exception('Maximum file size is 5 MB.', 400);
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $originalExtension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($type === 'image' && !str_starts_with((string)$mime, 'image/')) {
        throw new Exception('Image uploads must have an image content type.', 400);
    }
    if ($type === 'dwf' && $originalExtension !== 'dwf' && !in_array($mime, ['model/vnd.dwf', 'application/octet-stream', 'application/gzip', 'application/x-gzip'], true)) {
        throw new Exception('Invalid DWF content type.', 400);
    }

    $db = api_db();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO assets (user_id, type) VALUES (?, ?)');
        $stmt->execute([(int)$sessionUserId, $type]);
        $assetId = (int)$db->lastInsertId();
        $objectPath = "users/{$sessionUserId}/{$assetId}";

        if (defined('ASSET_STORAGE_DRIVER') && ASSET_STORAGE_DRIVER === 'firebase' && class_exists('Google\\Cloud\\Storage\\StorageClient')) {
            $storage = new \Google\Cloud\Storage\StorageClient(['keyFilePath' => FIREBASE_KEY_PATH]);
            $bucket = $storage->bucket(FIREBASE_BUCKET_NAME);
            $bucket->upload(fopen($file['tmp_name'], 'rb'), [
                'name' => $objectPath,
                'metadata' => ['contentType' => $mime]
            ]);
        } elseif (assetStorageIsLocal()) {
            $directory = localAssetDirectory((int)$sessionUserId);
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new Exception('The CDN directory could not be created.', 500);
            }

            $extension = $type === 'dwf' ? 'dwf' : match ((string)$mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'bin'
            };
            $target = $directory . DIRECTORY_SEPARATOR . $assetId . '.' . $extension;
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                throw new Exception('The file could not be stored in the CDN directory.', 500);
            }
        } else {
            throw new Exception('Firebase Storage client is not installed.', 500);
        }

        $db->commit();
        echo json_encode([
            'status' => 'success',
            'asset' => ['id' => $assetId, 'type' => $type]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function extractYoutubeId(string $url): ?string
{
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) return null;

    $host = strtolower($parts['host']);
    if (str_contains($host, 'youtu.be')) {
        $id = trim($parts['path'] ?? '', '/');
    } elseif (str_contains($host, 'youtube.com')) {
        parse_str($parts['query'] ?? '', $query);
        $id = (string)($query['v'] ?? '');
        if ($id === '' && preg_match('#/embed/([^/]+)#', $parts['path'] ?? '', $match)) $id = $match[1];
    } else {
        return null;
    }

    return preg_match('/^[A-Za-z0-9_-]{6,}$/', $id) ? $id : null;
}

function handleAssetsApi(string $action = ''): void
{
    // Veritabanı bağlantısı ve global değişkenler
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    header('Content-Type: application/json; charset=utf-8');

    if ($action === 'upload') {
        try {
            if ($method !== 'POST') throw new Exception('Method Not Allowed.', 405);
            handleAssetUpload();
        } catch (Throwable $e) {
            // PDO SQLSTATE codes such as "42S22" are strings, not HTTP status codes.
            $errorCode = $e->getCode();
            $httpCode = is_int($errorCode) && $errorCode >= 400 && $errorCode <= 599
                ? $errorCode
                : 500;
            http_response_code($httpCode);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        return;
    }

    try {
        switch ($method) {
            case 'GET':
                // Örnek kullanım: GET /api/v1/assets/123 veya GET /api/v1/assets?id=123
                $assetIds = array_values(array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? '')))));
                if ($assetIds) {
                    $placeholders = implode(',', array_fill(0, count($assetIds), '?'));
                    $assetQuery = "SELECT * FROM assets WHERE id IN ({$placeholders})";
                    $assetParams = $assetIds;
                    $assetType = strtolower(trim((string)($_GET['type'] ?? '')));
                    if ($assetType !== '') {
                        $assetQuery .= ' AND type = ?';
                        $assetParams[] = $assetType;
                    }

                    $db = api_db();
                    $stmt = $db->prepare($assetQuery);
                    $stmt->execute($assetParams);
                    $assetRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    $assetData = [];
                    foreach ($assetRows as $assetRow) {
                        $assetData[] = buildAssetInfoPayload((int)($assetRow['user_id'] ?? 0), $assetRow);
                    }

                    echo json_encode([
                        'status' => 'success',
                        'assets' => $assetData
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                }

                $assetId = (int)($action !== '' ? $action : ($_GET['id'] ?? 0));

                if ($assetId <= 0) {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing asset ID.'], JSON_UNESCAPED_UNICODE);
                    return;
                }

                // Veritabanından asset bilgisini çek
                $db = api_db();
                $assetType = strtolower(trim((string)($_GET['type'] ?? '')));
                $query = $assetType !== ''
                    ? 'SELECT * FROM assets WHERE id = :id AND type = :type LIMIT 1'
                    : 'SELECT * FROM assets WHERE id = :id LIMIT 1';
                $stmt = $db->prepare($query);
                $params = [':id' => $assetId];
                if ($assetType !== '') $params[':type'] = $assetType;
                $stmt->execute($params);
                $asset = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$asset) {
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Asset not found.'], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $creatorId = (int)($asset['user_id'] ?? $asset['creator_id'] ?? 0);
                
                // Senin buildAssetInfoPayload fonksiyonunla Signed URL'i üretip yanıtı hazırlıyoruz
                $responseData = buildAssetInfoPayload($creatorId, $asset);

                echo json_encode([
                    'status' => 'success',
                    'asset'  => $responseData
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
            'message' => 'Server error: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}
