<?php
declare(strict_types=1);

function handleAdminApi(string $action = ''): void
{
    header('Content-Type: application/json; charset=utf-8');
    $userId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
    if ($userId <= 0) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
        return;
    }

    $db = api_db();
    $adminStmt = $db->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
    $adminStmt->execute([$userId]);
    if ((int)$adminStmt->fetchColumn() !== 1) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Admin access required.']);
        return;
    }

    if ($action !== 'dashboard' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Admin endpoint not found.']);
        return;
    }

    $stats = $db->query("SELECT COUNT(*) AS total_users, SUM(last_login >= NOW() - INTERVAL 1 DAY) AS active_24h FROM users")->fetch(PDO::FETCH_ASSOC) ?: [];
    echo json_encode([
        'status' => 'success',
        'stats' => [
            'total_users' => (int)($stats['total_users'] ?? 0),
            'active_24h' => (int)($stats['active_24h'] ?? 0),
            'active_bans' => (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM user_bans WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())")->fetchColumn(),
            'total_games' => (int)$db->query('SELECT COUNT(*) FROM games')->fetchColumn()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
