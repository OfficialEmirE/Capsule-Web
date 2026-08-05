<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!function_exists('api_db')) {
    require_once ROOT_PATH . 'api/config.php';
}

$isAdmin = false;
$activeAnnouncement = null;

if (isset($_SESSION['user_id'])) {

    try {

        $db = api_db();

        $adminCheck = $db->prepare("
            SELECT is_admin
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $adminCheck->execute([
            $_SESSION['user_id']
        ]);

        $adminUser = $adminCheck->fetch(PDO::FETCH_ASSOC);

        if ($adminUser && (int)$adminUser['is_admin'] === 1) {
            $isAdmin = true;
        }

    } catch (Throwable $e) {
        $isAdmin = false;
    }

}

try {
    $announcementDb = api_db();
    $announcementStmt = $announcementDb->query("SELECT a.message, a.created_at, a.expires_at, u.username AS author_name
        FROM announcements a
        LEFT JOIN users u ON u.id = a.created_by
        ORDER BY a.id DESC
        LIMIT 1");
    $activeAnnouncement = $announcementStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($activeAnnouncement && $activeAnnouncement['expires_at'] !== null && strtotime($activeAnnouncement['expires_at']) <= time()) {
        $activeAnnouncement = null;
    }
} catch (Throwable $e) {
    $activeAnnouncement = null;
}

$announcementMessageHtml = '';
if ($activeAnnouncement) {
    $escapedAnnouncement = htmlspecialchars($activeAnnouncement['message'], ENT_QUOTES, 'UTF-8');
    $announcementMessageHtml = preg_replace_callback(
        '~https?://[^\s<]+~i',
        static function (array $match): string {
            $url = rtrim($match[0], '.,!?;:)');
            $trailing = substr($match[0], strlen($url));
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>' . $trailing;
        },
        $escapedAnnouncement
    );
    $announcementMessageHtml = nl2br($announcementMessageHtml ?? $escapedAnnouncement);
}
?>  
<nav class="navbar">
    <div class="navbar-inner">
        <div style="display:flex;align-items:center;">
            <a href="http://capsule.my.to" class="nav-logo">
                <img src="/assets/images/CapsuleLogoBeta.png" alt="Capsule Logo" style="height:28px;">
            </a>
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/games">Games</a></li>
                <li><a href="/develop">Create</a></li>
                <li><a href="/avatar">Avatar</a></li>
                <li><a href="/download">Download</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>

            <span class="nav-welcome" style="margin-right: 5px; font-weight: bold; color: #333; display: inline-flex; align-items: center;">
                <a href="/users/<?php echo $_SESSION['user_id']; ?>">
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </a>
            </span>


            <?php if ($isAdmin): ?>
                <a href="/admin" 
                style="
                text-decoration:none;
                padding:5px 10px;
                border:1px solid #ccc;
                border-radius:3px;
                background:#02b757;
                color:white;
                font-size:13px;
                margin-right:5px;
                cursor:pointer;">
                    Admin Panel
                </a>
            <?php endif; ?>


            <a href="#" 
            onclick="handleLogout(event)" 
            class="btn-logout" 
            style="
            text-decoration:none;
            padding:5px 10px;
            border:1px solid #ccc;
            border-radius:3px;
            background:#f8f8f8;
            color:#333;
            font-size:13px;
            cursor:pointer;">
                Log Out
            </a>
            <?php else: ?>
                <!-- Giriş Yapılmamışsa Gösterilecek Kısım -->
                <a href="/auth/register" class="btn-signup">Sign Up</a>
                <span class="nav-separator">or</span>
                <a href="/auth/login" class="btn-login">Log In</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if ($activeAnnouncement): ?>
<div class="announcement-bar" role="status">
    <div class="announcement-inner">
        <span class="announcement-message"><?php echo $announcementMessageHtml; ?></span>
    </div>
</div>
<?php endif; ?>

<style>
    .announcement-bar {
        background: #fff8d9;
        border-bottom: 1px solid #e4d58a;
        color: #5f531d;
        font-size: 12px;
    }
    .announcement-inner {
        width: 970px;
        margin: 0 auto;
        padding: 8px 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .announcement-message { white-space: normal; font-weight: 600; }
    .announcement-message a { color: #6d5b00; text-decoration: underline; }
    @media (max-width: 990px) {
        .announcement-inner { width: calc(100% - 24px); }
    }
</style>

<script>
/**
 * Oturumu güvenli bir şekilde kapatıp kullanıcıyı anasayfaya yönlendirir.
 */
async function handleLogout(event) {
    event.preventDefault();
    
    try {
        const response = await fetch('/api/v1/auth/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8'
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.status === 'success') {
            // Başarılıysa sayfayı anasayfaya yönlendir veya yenile
            window.location.href = '/';
        } else {
            console.error('Logout failed:', result.message);
            // Hata durumunda fallback olarak yine de sayfayı yenileyebiliriz
            window.location.reload();
        }
    } catch (error) {
        console.error('Network error during logout:', error);
        window.location.reload();
    }
}
</script>
