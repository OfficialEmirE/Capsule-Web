<?php
declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/'); 
}

// Config dosyasını dahil ediyoruz
$configPath = ROOT_PATH . 'api/config.php';
if (!file_exists($configPath)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error: config missing']);
    exit;
}
require_once $configPath;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- SECURITY & AUTHORIZATION CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login");
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$isAdmin = false;
$db = null;

try {
    // The admin page is already running on the same server and session.
    // Avoid a self-cURL request: it creates an extra hit and can deadlock sessions.
    $db = api_db();
    $adminStmt = $db->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
    $adminStmt->execute([$currentUserId]);
    $isAdmin = (int)$adminStmt->fetchColumn() === 1;
} catch (Throwable $e) {
    $isAdmin = false;
}

if (!$isAdmin) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    exit('Forbidden: Admin access required.');
}

$actionMessage = "";
$actionStatus = "";

try {
    if (!$db instanceof PDO) $db = api_db();

    // --- HANDLE POST ACTIONS (BAN / UNBAN / WARNING / RESOLVE REPORT) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'], $_POST['target_username'])) {
            $targetUser = trim($_POST['target_username']);
            $action = $_POST['action'];

            $userStmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $userStmt->execute([$targetUser]);
            $userData = $userStmt->fetch();

            if ($userData) {
                $userId = (int)$userData['id'];

                if ($action === 'ban') {
                    $reason = trim($_POST['reason'] ?? 'No reason provided');
                    $durationHours = (int)($_POST['duration'] ?? 0);

                    $deactivate = $db->prepare("UPDATE user_bans SET is_active = 0 WHERE user_id = ?");
                    $deactivate->execute([$userId]);

                    $expiresAt = null;
                    if ($durationHours > 0) {
                        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationHours} hours"));
                    }

                    $insertBan = $db->prepare("INSERT INTO user_bans (user_id, reason, banned_at, expires_at, is_active) VALUES (?, ?, NOW(), ?, 1)");
                    $insertBan->execute([$userId, $reason, $expiresAt]);

                    $actionMessage = "User '{$targetUser}' has been successfully banned.";
                    $actionStatus = "success";
                } 
                elseif ($action === 'warning') {
                    $reason = trim($_POST['reason'] ?? 'No reason provided');
                    $warningReason = "[WARNING] " . $reason;
                    
                    $durationHours = (int)($_POST['duration'] ?? 0);
                    $expiresAt = null;
                    if ($durationHours > 0) {
                        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationHours} hours"));
                    }

                    $deactivate = $db->prepare("UPDATE user_bans SET is_active = 0 WHERE user_id = ?");
                    $deactivate->execute([$userId]);

                    $insertWarning = $db->prepare("INSERT INTO user_bans (user_id, reason, banned_at, expires_at, is_active) VALUES (?, ?, NOW(), ?, 1)");
                    $insertWarning->execute([$userId, $warningReason, $expiresAt]);

                    $actionMessage = "User '{$targetUser}' has been officially warned.";
                    $actionStatus = "warning";
                } 
                elseif ($action === 'unban') {
                    $unbanStmt = $db->prepare("UPDATE user_bans SET is_active = 0 WHERE user_id = ?");
                    $unbanStmt->execute([$userId]);

                    $actionMessage = "All active bans and warnings for '{$targetUser}' have been lifted.";
                    $actionStatus = "success";
                }
            } else {
                $actionMessage = "User not found.";
                $actionStatus = "error";
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'create_announcement') {
            $announcementMessage = trim((string)($_POST['announcement_message'] ?? ''));
            $durationHours = max(0, (int)($_POST['announcement_duration'] ?? 0));

            if ($announcementMessage === '') {
                $actionMessage = 'Announcement message is required.';
                $actionStatus = 'error';
            } else {
                $expiresAt = null;
                if ($durationHours > 0) {
                    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationHours} hours"));
                }

                $announcementStmt = $db->prepare('INSERT INTO announcements (message, created_by, expires_at) VALUES (?, ?, ?)');
                $announcementStmt->execute([$announcementMessage, $currentUserId, $expiresAt]);
                $actionMessage = 'Announcement published successfully.';
                $actionStatus = 'success';
            }
        } elseif (isset($_POST['resolve_report_id'])) {
            $reportId = (int)$_POST['resolve_report_id'];
            $updateReport = $db->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
            $updateReport->execute([$reportId]);
            $actionMessage = "Report #{$reportId} marked as resolved.";
            $actionStatus = "success";
        }
    }

    // --- İSTATİSTİKLER ---
    $userStats = $db->query("SELECT COUNT(*) AS total_users, SUM(last_login >= NOW() - INTERVAL 1 DAY) AS active_24h FROM users")->fetch(PDO::FETCH_ASSOC) ?: [];
    $totalUsers = (int)($userStats['total_users'] ?? 0);
    $active24h = (int)($userStats['active_24h'] ?? 0);
    
    // Aktif Ban/Uyarı Sayısını Hesaplama
    $bannedUsersCount = $db->query("SELECT COUNT(DISTINCT user_id) FROM user_bans WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())")->fetchColumn();

    try {
        $totalGames = $db->query("SELECT COUNT(*) FROM games")->fetchColumn();
    } catch (Exception $e) {
        $totalGames = 0;
    }

    // --- TABLO VERİLERİ ---
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // 1. Tüm Kullanıcılar
    $allUsersStmt = $db->prepare("SELECT id, username, email, avatar, last_login, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
    $allUsersStmt->bindValue(1, $limit, PDO::PARAM_INT);
    $allUsersStmt->bindValue(2, $offset, PDO::PARAM_INT);
    $allUsersStmt->execute();
    $allUsers = $allUsersStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. En Son Aktif Kullanıcılar
    $lastActiveUsers = $db->query("SELECT id, username, email, avatar, last_login FROM users WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Oyunlar Listesi
    try {
        $gamesList = $db->query("
            SELECT 
                g.id, 
                g.name, 
                g.max_players, 
                g.public, 
                u.username as owner_name 
            FROM games g 
            LEFT JOIN users u ON g.ownerUserId = u.id 
            ORDER BY g.id DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $gamesList = [];
    }

    // 4. Banlanan / Uyarı Alan Kullanıcılar Listesi
    try {
        $bannedUsersList = $db->query("
            SELECT 
                b.id as ban_id,
                b.reason,
                b.banned_at,
                b.expires_at,
                b.is_active,
                u.id as user_id,
                u.username,
                u.avatar
            FROM user_bans b
            JOIN users u ON b.user_id = u.id
            WHERE b.is_active = 1 AND (b.expires_at IS NULL OR b.expires_at > NOW())
            ORDER BY b.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $bannedUsersList = [];
    }

    // 5. Gelen Raporlar Listesi (Reports Table - Hedef kullanıcı adı ve oyun adı eklendi)
    try {
        $reportsList = $db->query("
            SELECT 
                r.id,
                r.target_type,
                r.target_id,
                r.reason,
                r.details,
                r.status,
                r.created_at,
                u_reporter.username AS reporter_name,
                u_target.username AS target_username,
                g_target.name AS target_game_name
            FROM reports r
            LEFT JOIN users u_reporter ON r.reporter_user_id = u_reporter.id
            LEFT JOIN users u_target ON (r.target_type = 'user' AND r.target_id = u_target.id)
            LEFT JOIN games g_target ON (r.target_type = 'game' AND r.target_id = g_target.id)
            ORDER BY r.id DESC
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $reportsList = [];
    }

    // 6. Announcements
    try {
        $announcementsList = $db->query("SELECT a.id, a.message, a.created_at, a.expires_at, u.username AS author_name
            FROM announcements a
            LEFT JOIN users u ON u.id = a.created_by
            ORDER BY a.id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $announcementsList = [];
    }

} catch (Exception $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred: ' . $e->getMessage()]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Capsule Beta</title>
    <?php include ROOT_PATH . 'includes/meta.php'; ?>
    <?php include ROOT_PATH . 'includes/icon.php'; ?>
    <link rel="stylesheet" href="/assets/css/Capsule.css">
    
    <style>
        .admin-wrapper { width: 100%; padding: 10px; }
        .admin-header { margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .stats-boxes { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px; }
        .stat-box h3 { margin: 0 0 5px 0; font-size: 12px; color: #666; text-transform: uppercase; }
        .stat-box p { margin: 0; font-size: 24px; font-weight: bold; color: #02b757; }
        
        .tools-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .tool-card { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 4px; }
        .tool-card h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 8px; font-size: 14px; color: #333; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 12px; margin-bottom: 4px; color: #555; }
        .form-control { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; box-sizing: border-box; }
        .btn-admin { padding: 6px 12px; border: none; border-radius: 3px; font-size: 12px; font-weight: bold; cursor: pointer; color: #fff; }
        .btn-danger { background: #d9534f; }
        .btn-warning { background: #f0ad4e; color: #fff; }
        .btn-success { background: #5cb85c; }
        
        .alert { padding: 10px; border-radius: 3px; margin-bottom: 15px; font-size: 13px; font-weight: bold; }
        .alert-success { background: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .alert-warning { background: #fcf8e3; color: #8a6d3b; border: 1px solid #faebcc; }
        .alert-error { background: #f2dede; color: #a94442; border: 1px solid #ebccd1; }

        .admin-tabs { display: flex; gap: 5px; border-bottom: 2px solid #ddd; margin-bottom: 15px; flex-wrap: wrap; }
        .tab-btn { padding: 8px 16px; background: #eee; border: 1px solid #ddd; border-bottom: none; cursor: pointer; font-weight: bold; font-size: 13px; border-radius: 4px 4px 0 0; }
        .tab-btn.active { background: #fff; border-color: #ddd #ddd #fff #ddd; color: #02b757; margin-bottom: -2px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .activity-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #ddd; }
        .activity-table th, .activity-table td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 13px; }
        .activity-table th { background: #f2f2f2; font-weight: bold; }
        .user-avatar-dot { width: 12px; height: 12px; display: inline-block; border-radius: 50%; border: 1px solid #999; margin-right: 6px; vertical-align: middle; }

        .pagination { margin-top: 10px; display: flex; gap: 5px; justify-content: flex-end; }
        .pagination a { padding: 4px 8px; border: 1px solid #ccc; background: #f9f9f9; text-decoration: none; color: #333; font-size: 12px; border-radius: 3px; }
        .pagination a.active { background: #02b757; color: white; border-color: #02b757; }
        
        .badge { padding: 3px 6px; font-size: 11px; font-weight: bold; border-radius: 3px; color: #fff; }
        .badge-danger { background-color: #d9534f; }
        .badge-warning { background-color: #f0ad4e; }
        .badge-info { background-color: #5bc0de; }
        .badge-success { background-color: #5cb85c; }
    </style>
</head>
<body>
    <?php include ROOT_PATH . 'includes/header.php'; ?>

    <div class="main-container">
        <div class="admin-wrapper">
            <div class="admin-header">
                <h2>Admin Control Panel</h2>
                <span style="font-size: 12px; color: #555;">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>!</span>
            </div>

            <?php if (!empty($actionMessage)): ?>
                <div class="alert alert-<?php echo $actionStatus; ?>">
                    <?php echo htmlspecialchars($actionMessage); ?>
                </div>
            <?php endif; ?>

            <div class="stats-boxes">
                <div class="stat-box">
                    <h3>Total Users</h3>
                    <p id="adminTotalUsers"><?php echo $totalUsers; ?></p>
                </div>
                <div class="stat-box">
                    <h3>24h Active Users</h3>
                    <p id="adminActiveUsers"><?php echo $active24h; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Total Games</h3>
                    <p id="adminTotalGames" style="color: #337ab7;"><?php echo $totalGames; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Active Bans / Warnings</h3>
                    <p id="adminActiveBans" style="color: #d9534f;"><?php echo $bannedUsersCount; ?></p>
                </div>
            </div>

            <div class="tools-grid">
                <div class="tool-card">
                    <h3>Advanced Moderation Tool</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="target_username">Target Username:</label>
                            <input type="text" id="target_username" name="target_username" class="form-control" placeholder="Username..." required>
                        </div>
                        <div class="form-group">
                            <label for="reason">Reason:</label>
                            <input type="text" id="reason" name="reason" class="form-control" placeholder="Why are they being moderated?">
                        </div>
                        <div class="form-group">
                            <label for="duration">Duration (Hours):</label>
                            <input type="number" id="duration" name="duration" class="form-control" placeholder="0 for permanent restriction" min="0" value="0">
                        </div>
                        <div style="display: flex; gap: 8px; margin-top: 15px; flex-wrap: wrap;">
                            <button type="submit" name="action" value="ban" class="btn-admin btn-danger">Apply Ban</button>
                            <button type="submit" name="action" value="warning" class="btn-admin btn-warning">Apply Warning</button>
                            <button type="submit" name="action" value="unban" class="btn-admin btn-success">Apply Unban</button>
                        </div>
                    </form>
                </div>

                <div class="tool-card">
                    <h3>Quick Moderation Actions</h3>
                    <div style="font-size: 12px; color: #555; line-height: 1.6;">
                        <p style="margin-top: 0; margin-bottom: 10px;">
                            <strong>Note:</strong> Set duration to <code>0</code> for permanent restrictions.
                        </p>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label style="font-weight: bold;">Quick User Search:</label>
                            <div style="display: flex; gap: 5px;">
                                <input type="number" id="quick_user_id" class="form-control" placeholder="User ID..." min="1">
                                <button type="button" class="btn-admin btn-success" onclick="goToUserProfile()">Go</button>
                            </div>
                        </div>
                        <ul style="margin: 0; padding-left: 18px; color: #666; font-size: 11px;">
                            <li><strong>Warnings:</strong> Prefix reasons with <code>[WARNING]</code>.</li>
                            <li><strong>Unban:</strong> Lifts all active bans and warnings for the user.</li>
                        </ul>
                    </div>
                </div>

                <div class="tool-card">
                    <h3>Publish Announcement</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="create_announcement">
                        <div class="form-group">
                            <label for="announcement_message">Announcement:</label>
                            <textarea id="announcement_message" name="announcement_message" class="form-control" rows="4" placeholder="Write an announcement..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="announcement_duration">Visible for (hours):</label>
                            <input type="number" id="announcement_duration" name="announcement_duration" class="form-control" min="0" value="0">
                            <small style="color:#777;">Use 0 to keep it until another announcement is published.</small>
                        </div>
                        <button type="submit" class="btn-admin btn-success">Publish Announcement</button>
                    </form>
                </div>
            </div>

            <!-- SEKMELER (TABS) -->
            <div class="admin-tabs">
                <button class="tab-btn active" onclick="openTab(event, 'all-users')">User List</button>
                <button class="tab-btn" onclick="openTab(event, 'last-active')">Last Active Users</button>
                <button class="tab-btn" onclick="openTab(event, 'banned-users')">Banned & Warned Users (<?php echo count($bannedUsersList); ?>)</button>
                <button class="tab-btn" onclick="openTab(event, 'games-list')">Games List</button>
                <button class="tab-btn" onclick="openTab(event, 'reports-list')">Reports List (<?php echo count($reportsList); ?>)</button>
                <button class="tab-btn" onclick="openTab(event, 'announcements-list')">Announcements (<?php echo count($announcementsList); ?>)</button>
            </div>

            <!-- TAB 1: TÜM KULLANICILAR -->
            <div id="all-users" class="tab-content active">
                <h3>Registered Users List</h3>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allUsers)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #777;">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($allUsers as $user): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td>
                                        <a href="/users/<?php echo $user['id']; ?>" target="_blank" style="text-decoration: none; color: inherit;">
                                            <span class="user-avatar-dot" style="background-color: <?php echo htmlspecialchars($user['avatar'] ?? '#ccc'); ?>;"></span>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo isset($user['created_at']) ? date('Y-m-d H:i', strtotime($user['created_at'])) : 'N/A'; ?></td>
                                    <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <?php 
                    $totalPages = ceil($totalUsers / $limit);
                    for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $page === $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- TAB 2: EN SON AKTİF OLAN KULLANICILAR -->
            <div id="last-active" class="tab-content">
                <h3>Last Active Users</h3>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Last Active Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lastActiveUsers)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #777;">No active user data.</td></tr>
                        <?php else: ?>
                            <?php foreach ($lastActiveUsers as $activeUser): ?>
                                <tr>
                                    <td>#<?php echo $activeUser['id']; ?></td>
                                    <td>
                                        <a href="/users/<?php echo $activeUser['id']; ?>" target="_blank" style="text-decoration: none; color: inherit;">
                                            <span class="user-avatar-dot" style="background-color: <?php echo htmlspecialchars($activeUser['avatar'] ?? '#ccc'); ?>;"></span>
                                            <strong><?php echo htmlspecialchars($activeUser['username']); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($activeUser['email'] ?? 'N/A'); ?></td>
                                    <td><span style="color: #02b757; font-weight: bold;"><?php echo date('Y-m-d H:i:s', strtotime($activeUser['last_login'])); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TAB 3: BANLANAN / UYARI ALAN KULLANICILAR LİSTESİ -->
            <div id="banned-users" class="tab-content">
                <h3>Banned & Warned Users</h3>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">User ID</th>
                            <th>Username</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Ban/Warn Date</th>
                            <th>Expires At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bannedUsersList)): ?>
                            <tr><td colspan="6" style="text-align: center; color: #777;">No active bans or warnings found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bannedUsersList as $banned): ?>
                                <?php $isWarning = (strpos($banned['reason'], '[WARNING]') === 0); ?>
                                <tr>
                                    <td>#<?php echo $banned['user_id']; ?></td>
                                    <td>
                                        <a href="/users/<?php echo $banned['user_id']; ?>" target="_blank" style="text-decoration: none; color: inherit;">
                                            <span class="user-avatar-dot" style="background-color: <?php echo htmlspecialchars($banned['avatar'] ?? '#ccc'); ?>;"></span>
                                            <strong><?php echo htmlspecialchars($banned['username']); ?></strong>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($isWarning): ?>
                                            <span class="badge badge-warning">WARNING</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">BAN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($banned['reason']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($banned['banned_at'])); ?></td>
                                    <td>
                                        <?php 
                                        if (empty($banned['expires_at'])) {
                                            echo '<span style="color: #d9534f; font-weight: bold;">Permanent</span>';
                                        } else {
                                            echo date('Y-m-d H:i', strtotime($banned['expires_at']));
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TAB 4: OYUNLAR LİSTESİ -->
            <div id="games-list" class="tab-content">
                <h3>Recent Games</h3>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Game ID</th>
                            <th>Game Title</th>
                            <th>Creator (Owner)</th>
                            <th>Max Players</th>
                            <th>Visibility</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gamesList)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #777;">No games found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($gamesList as $game): ?>
                                <tr>
                                    <td>#<?php echo $game['id']; ?></td>
                                    <td>
                                        <strong>
                                            <a href="/games/<?php echo $game['id']; ?>" target="_blank" style="color: #095fb8; text-decoration: none;">
                                                <?php echo htmlspecialchars($game['name']); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($game['owner_name'] ?? 'System / Unknown'); ?></td>
                                    <td><?php echo (int)($game['max_players'] ?? 0); ?> Players</td>
                                    <td>
                                        <?php if ((int)($game['public'] ?? 1) === 1): ?>
                                            <span style="color: #02b757; font-weight: bold;">Public</span>
                                        <?php else: ?>
                                            <span style="color: #d9534f; font-weight: bold;">Private</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TAB 5: ŞİKAYETLER VE RAPORLAR (REPORTS LIST) -->
            <div id="reports-list" class="tab-content">
                <h3>User & Game Reports</h3>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Reporter</th>
                            <th>Type</th>
                            <th>Target</th>
                            <th>Reason</th>
                            <th>Details</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportsList)): ?>
                            <tr><td colspan="8" style="text-align: center; color: #777;">No reports submitted yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reportsList as $report): ?>
                                <tr>
                                    <td>#<?php echo $report['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($report['reporter_name'] ?? 'Anonymous/Deleted'); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo ($report['target_type'] ?? '') === 'user' ? 'badge-info' : 'badge-warning'; ?>">
                                            <?php echo strtoupper(htmlspecialchars($report['target_type'] ?? 'UNKNOWN')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (($report['target_type'] ?? '') === 'user'): ?>
                                            <a href="/users/<?php echo $report['target_id']; ?>" target="_blank" style="text-decoration: none;">
                                                <strong><?php echo htmlspecialchars($report['target_username'] ?? ('User #' . $report['target_id'])); ?></strong>
                                            </a>
                                        <?php else: ?>
                                            <a href="/games/<?php echo $report['target_id']; ?>" target="_blank" style="text-decoration: none;">
                                                <strong><?php echo htmlspecialchars($report['target_game_name'] ?? ('Game #' . $report['target_id'])); ?></strong>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($report['reason'] ?? ''); ?></code></td>
                                    <td><small><?php echo !empty($report['details']) ? htmlspecialchars($report['details']) : '<em>No details provided</em>'; ?></small></td>
                                    <td><?php echo isset($report['created_at']) ? date('Y-m-d H:i', strtotime($report['created_at'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php if (($report['status'] ?? 'pending') === 'resolved'): ?>
                                            <span class="badge badge-success">Resolved</span>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="resolve_report_id" value="<?php echo $report['id']; ?>">
                                                <button type="submit" class="btn-admin btn-success" style="padding: 2px 6px; font-size: 10px;">Mark Resolved</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="announcements-list" class="tab-content">
                <h3>Announcements</h3>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Message</th>
                            <th>Posted By</th>
                            <th>Created</th>
                            <th>Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($announcementsList)): ?>
                            <tr><td colspan="5" style="text-align:center;color:#777;">No announcements found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($announcementsList as $announcement): ?>
                                <tr>
                                    <td>#<?php echo (int)$announcement['id']; ?></td>
                                    <td style="white-space:pre-wrap;"><?php echo htmlspecialchars($announcement['message']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($announcement['author_name'] ?? 'Unknown Admin'); ?></strong></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?></td>
                                    <td><?php echo $announcement['expires_at'] ? date('Y-m-d H:i', strtotime($announcement['expires_at'])) : 'Until replaced'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <?php include ROOT_PATH . 'includes/bottom.php'; ?>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        function goToUserProfile() {
            var userId = document.getElementById('quick_user_id').value;
            if (userId && userId > 0) {
                window.open('/users/' + userId, '_blank');
            } else {
                alert('Please enter a valid User ID');
            }
        }

        fetch('/api/v1/admin/dashboard', { headers: { Accept: 'application/json' } })
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (data) {
                if (!data || data.status !== 'success' || !data.stats) return;
                var stats = data.stats;
                document.getElementById('adminTotalUsers').textContent = stats.total_users;
                document.getElementById('adminActiveUsers').textContent = stats.active_24h;
                document.getElementById('adminActiveBans').textContent = stats.active_bans;
                document.getElementById('adminTotalGames').textContent = stats.total_games;
            })
            .catch(function () {});
    </script>
</body>
</html>
