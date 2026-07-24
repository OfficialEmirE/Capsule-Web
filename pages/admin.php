<?php
declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/'); 
}

// Include config (API configuration, session parameters, and DB methods are already handled inside)
$configPath = ROOT_PATH . 'api/config.php';
if (!file_exists($configPath)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal Server Error: config missing']);
    exit;
}
require_once $configPath;

/// --- SECURITY & AUTHORIZATION CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login");
    exit;
}

try {
    $db = api_db();

    $stmt = $db->prepare("
        SELECT is_admin
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);

    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser || (int)$currentUser['is_admin'] !== 1) {
        http_response_code(403);
        exit('Forbidden');
    }

} catch (Throwable $e) {
    http_response_code(500);
    exit('Database error');
}

$actionMessage = "";
$actionStatus = "";

try {
    // --- HANDLE POST ACTIONS (BAN / UNBAN / WARNING) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['target_username'])) {
        $targetUser = trim($_POST['target_username']);
        $action = $_POST['action'];

        // Find user ID from username
        $userStmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $userStmt->execute([$targetUser]);
        $userData = $userStmt->fetch();

        if ($userData) {
            $userId = (int)$userData['id'];

            if ($action === 'ban') {
                $reason = trim($_POST['reason'] ?? 'No reason provided');
                $durationHours = (int)($_POST['duration'] ?? 0); // 0 means permanent

                // Deactivate any existing active bans/warnings first
                $deactivate = $db->prepare("UPDATE user_bans SET is_active = 0 WHERE user_id = ?");
                $deactivate->execute([$userId]);

                // Calculate expiry time
                $expiresAt = null;
                if ($durationHours > 0) {
                    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationHours} hours"));
                }

                // Insert new ban record
                $insertBan = $db->prepare("INSERT INTO user_bans (user_id, reason, expires_at, is_active) VALUES (?, ?, ?, 1)");
                $insertBan->execute([$userId, $reason, $expiresAt]);

                $actionMessage = "User '{$targetUser}' has been successfully banned.";
                $actionStatus = "success";
            } 
            // --- YENİ EKLENEN UYARI (WARNING) MANTIĞI ---
            elseif ($action === 'warning') {
                $reason = trim($_POST['reason'] ?? 'No reason provided');
                // Sebebin başına uyarı olduğunu belirten bir etiket ekliyoruz ki UI'da ayırt edilebilsin
                $warningReason = "[WARNING] " . $reason;
                
                // Uyarılar genellikle kalıcı hesap kapatması olmadığı için kısa süreli (örn: 24 saat) veya direkt süresiz bir uyarı kartı olabilir.
                // İstersen duration alanından gelen saati uyarının ekranda kalma süresi yapabilirsin:
                $durationHours = (int)($_POST['duration'] ?? 0);
                $expiresAt = null;
                if ($durationHours > 0) {
                    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationHours} hours"));
                }

                // Mevcut aktif cezaları temizleyip yeni uyarı kaydı atıyoruz
                $deactivate = $db->prepare("UPDATE user_bans SET is_active = 0 WHERE user_id = ?");
                $deactivate->execute([$userId]);

                $insertWarning = $db->prepare("INSERT INTO user_bans (user_id, reason, expires_at, is_active) VALUES (?, ?, ?, 1)");
                $insertWarning->execute([$userId, $warningReason, $expiresAt]);

                $actionMessage = "User '{$targetUser}' has been officially warned.";
                $actionStatus = "warning"; // CSS'de sarı renk gösterecek
            } 
            elseif ($action === 'unban') {
                // Lift ban/warning by deactivating active records
                $unbanStmt = $db->prepare("UPDATE user_bans SET is_active = 0 WHERE user_id = ?");
                $unbanStmt->execute([$userId]);

                $actionMessage = "All active bans and warnings for '{$targetUser}' have been lifted.";
                $actionStatus = "success";
            }
        } else {
            $actionMessage = "User not found.";
            $actionStatus = "error";
        }
    }

    // 1. Total Registered Users
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // 2. Active Users in Last 24 Hours
    $active24h = $db->query("SELECT COUNT(*) FROM users WHERE last_login >= NOW() - INTERVAL 1 DAY")->fetchColumn();

    // 3. Current Active Banned Users Count
    $bannedUsers = $db->query("SELECT COUNT(DISTINCT user_id) FROM user_bans WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())")->fetchColumn();

    // 4. Total Captchas Solved (Falls back to total users if table doesn't exist)
    try {
        $totalCaptchas = $db->query("SELECT COUNT(*) FROM captcha_logs")->fetchColumn();
    } catch (Exception $e) {
        $totalCaptchas = $totalUsers; 
    }

    // 5. Recent 10 Registered Users
    $recentUsers = $db->query("SELECT id, username, email, avatar, last_login FROM users ORDER BY id DESC LIMIT 10")->fetchAll();

} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
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

        .activity-table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; border: 1px solid #ddd; }
        .activity-table th, .activity-table td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 13px; }
        .activity-table th { background: #f2f2f2; font-weight: bold; }
        .user-avatar-dot { width: 12px; height: 12px; display: inline-block; border-radius: 50%; border: 1px solid #999; margin-right: 6px; vertical-align: middle; }
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
                    <p><?php echo $totalUsers; ?></p>
                </div>
                <div class="stat-box">
                    <h3>24h Active Users</h3>
                    <p><?php echo $active24h; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Active Bans / Warnings</h3>
                    <p style="color: #d9534f;"><?php echo $bannedUsers; ?></p>
                </div>
                <!--<div class="stat-box">
                    <h3>Altcha Solved</h3>
                    <p style="color: #f0ad4e;"><?php echo $totalCaptchas; ?></p>
                </div> -->  
            </div>

            <div class="tools-grid">
                <!-- Advanced Moderation Tool -->
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
                    <h3>Environment Info</h3>
                    <ul style="margin: 0; padding-left: 20px; font-size: 12px; line-height: 1.8; color: #555;">
                        <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                        <li><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                        <li><strong>Secure Session:</strong> <?php echo isset($_SERVER['HTTPS']) ? 'Yes' : 'No'; ?></li>
                    </ul>
                </div>
            </div>

            <h3>Recent User Registrations</h3>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentUsers)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #777;">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td>
                                    <span class="user-avatar-dot" style="background-color: <?php echo htmlspecialchars($user['avatar'] ?? '#ccc'); ?>;"></span>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                        echo $user['last_login'] 
                                            ? date('Y-m-d H:i', strtotime($user['last_login'])) 
                                            : 'Never'; 
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include ROOT_PATH . 'includes/bottom.php'; ?>
</body>
</html>