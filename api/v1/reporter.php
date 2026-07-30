<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . 'api/config.php';

/**
 * Main API Gateway for Reporter Endpoint
 */
function handleReporterApi(string $action): void
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $db = api_db();

        switch (strtolower($action)) {
            case 'submit':
            case 'create':
                handleSubmitReport($db);
                break;
            case 'my-reports':
            case 'list':
                handleListUserReports($db);
                break;
            default:
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid reporter endpoint. Supported actions: submit, list'
                ], JSON_UNESCAPED_UNICODE);
                exit;
        }
    } catch (Exception $e) {
        $code = (int)$e->getCode();
        $httpCode = ($code >= 400 && $code <= 505) ? $code : 500;
        http_response_code($httpCode);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * 1. SUBMIT REPORT ENDPOINT (/api/v1/reporter/submit)
 */
function handleSubmitReport(PDO $db): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method Not Allowed. Only POST requests are permitted.', 405);
    }

    $sessionUserId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    if (!$sessionUserId) {
        throw new Exception('Unauthorized: You must be logged in to submit a report.', 401);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $targetType = trim($input['target_type'] ?? '');
    $targetId   = (int)($input['target_id'] ?? 0);
    $reason     = trim($input['reason'] ?? '');
    $details    = trim($input['details'] ?? '');

    $allowedTypes = ['user', 'game', 'asset', 'comment'];

    if (!in_array($targetType, $allowedTypes, true)) {
        throw new Exception('Invalid target_type. Allowed values: user, game, asset, comment.', 400);
    }

    if ($targetId <= 0) {
        throw new Exception('Validation Failed: target_id must be a valid positive integer.', 400);
    }

    if (empty($reason)) {
        throw new Exception('Validation Failed: reason field is required.', 400);
    }

    // Spam Engelleme: Son 5 dakika içinde aynı hedefe rapor var mı?
    $checkStmt = $db->prepare("
        SELECT id FROM reports 
        WHERE reporter_user_id = ? AND target_type = ? AND target_id = ? 
        AND created_at > NOW() - INTERVAL 5 MINUTE
        LIMIT 1
    ");
    $checkStmt->execute([$sessionUserId, $targetType, $targetId]);
    if ($checkStmt->fetchColumn()) {
        throw new Exception('You have already reported this item recently. Please wait before submitting another report.', 429);
    }

    // Veritabanına Rapor Kaydı
    $sql = "INSERT INTO reports (reporter_user_id, target_type, target_id, reason, details) 
            VALUES (:reporter_user_id, :target_type, :target_id, :reason, :details)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':reporter_user_id' => $sessionUserId,
        ':target_type'      => $targetType,
        ':target_id'        => $targetId,
        ':reason'           => $reason,
        ':details'          => $details !== '' ? $details : null
    ]);

    $reportId = (int)$db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Report submitted successfully.',
        'report_id' => $reportId
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 2. LIST USER REPORTS ENDPOINT (/api/v1/reporter/list)
 */
function handleListUserReports(PDO $db): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method Not Allowed. Only GET requests are permitted.', 405);
    }

    $sessionUserId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    if (!$sessionUserId) {
        throw new Exception('Unauthorized: You must be logged in to view your reports.', 401);
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE reporter_user_id = ?");
    $countStmt->execute([$sessionUserId]);
    $totalCount = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT id, target_type, target_id, reason, details, status, created_at 
        FROM reports 
        WHERE reporter_user_id = :uid 
        ORDER BY id DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':uid', $sessionUserId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'status' => 'success',
        'reports' => $reports,
        'pagination' => [
            'current_page' => $page,
            'total_pages'  => (int)ceil($totalCount / $limit),
            'total_reports' => $totalCount
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}