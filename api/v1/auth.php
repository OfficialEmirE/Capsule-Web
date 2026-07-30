<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- GÜVENLİ VE GELİŞMİŞ SESSION / COOKIE YAPILANDIRMASI ---
$lifetime = 30 * 24 * 60 * 60; // 30 Gün

// EĞER OTURUM ZATEN BAŞLATILMIŞSA ÖNCE KAPATIYORUZ!
// Bu sayede ini_set() ve session_set_cookie_params() hata vermez.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// HTTPS Bağlantısını Akıllıca Tespit Et (Proxy / Load Balancer Desteği)
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

// Güvenlik Parametrelerini Set Ediyoruz
ini_set('session.use_strict_mode', '1');      // Tanımsız Session ID'leri reddet
ini_set('session.use_only_cookies', '1');     // Sadece Cookie kullan (URL injection'a izin verme)
ini_set('session.use_trans_sid', '0');        // URL'de Session ID gösterme
ini_set('session.gc_maxlifetime', (string)$lifetime);

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path'     => '/',
    'domain'   => '', // Otomatik mevcut domain
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Ayarlar yapıldıktan sonra oturumu güvenle açıyoruz
session_start();

// Config dosyasını dahil ediyoruz. 2 seviye yukarı çıkarak ana dizindeki config.php'yi arar.
$configPath = dirname(__DIR__, 1) . '/config.php';

require_once dirname(__DIR__, 2) . '/vendor/phpmailer/src/Exception.php';
require_once dirname(__DIR__, 2) . '/vendor/phpmailer/src/PHPMailer.php';
require_once dirname(__DIR__, 2) . '/vendor/phpmailer/src/SMTP.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["status" => "error", "message" => "Configuration file not found."], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $configPath;

/**
 * Handles the Auth API requests.
 * 
 * @param string $action 'login', 'register', 'logout', 'forgot', 'reset' or 'detoken'
 */
function handleAuthApi(string $action): void
{
    header('Content-Type: application/json; charset=utf-8');

    // 'detoken' GET veya POST isteklerini kabul edebilir; diğerleri sadece POST kabul eder.
    if ($action !== 'detoken' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Only POST requests are allowed."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Get database connection from config.php's api_db() function
    $db = null;
    if (!in_array($action, ['logout', 'detoken'], true)) {
        try {
            $db = api_db();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database connection failed."], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Parse incoming input (JSON format or raw POST)
    $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;

    switch ($action) {
        // --------------------------------------------------------------------------
        // DETOKEN ACTION (SESSION VERİLERİNİ SORGULAMA)
        // --------------------------------------------------------------------------
        case 'detoken':
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Oturum çerezi yoksa veya oturum açılmamışsa
            if (empty($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode([
                    "status" => "error",
                    "authenticated" => false,
                    "message" => "Unauthorized or session expired."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Aktif oturum verilerini döndür
            echo json_encode([
                "status" => "success",
                "authenticated" => true,
                "user" => [
                    "id" => $_SESSION['user_id'],
                    "username" => $_SESSION['username'] ?? null,
                    "avatar" => $_SESSION['avatar'] ?? null
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        // --------------------------------------------------------------------------
        // REGISTER ACTION (EMAIL OPTIONAL)
        // --------------------------------------------------------------------------
        case 'register':
            $username = trim($input['username'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';

            if (empty($username) || empty($password)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Please fill in all required fields."], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Invalid email address format."], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (strlen($password) < 6) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Password must be at least 6 characters long."], JSON_UNESCAPED_UNICODE);
                exit;
            }

            try {
                if (!empty($email)) {
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                } else {
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                }

                if ($stmt->fetch()) {
                    http_response_code(409);
                    echo json_encode(["status" => "error", "message" => "Username or Email is already registered."], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $colors = ['#3498db', '#2ecc71', '#9b59b6', '#e67e22', '#e74c3c', '#1abc9c', '#FFFFFF'];
                $random_color = $colors[array_rand($colors)];

                $dbEmail = !empty($email) ? $email : null;
                $insert = $db->prepare("INSERT INTO users (username, email, password_hash, avatar) VALUES (?, ?, ?, ?)");
                $insert->execute([$username, $dbEmail, $password_hash, $random_color]);

                $newUserId = (int)$db->lastInsertId();

                $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update->execute([$newUserId]);

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                // GÜVENLİK: Fixation Önleme (Yeni ID Üret)
                session_regenerate_id(true);

                $_SESSION['user_id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['avatar'] = $random_color;

                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Registered and logged in successfully!"], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "A system error occurred during registration."], JSON_UNESCAPED_UNICODE);
            }
            break;

        // --------------------------------------------------------------------------
        // LOGIN ACTION
        // --------------------------------------------------------------------------
        case 'login':
            $usernameOrEmail = trim($input['username_or_email'] ?? '');
            $password = $input['password'] ?? '';

            if (empty($usernameOrEmail) || empty($password)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Username/Email and Password are required."], JSON_UNESCAPED_UNICODE);
                exit;
            }

            try {
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update->execute([$user['id']]);

                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    // GÜVENLİK: Fixation Önleme (Eski ID'yi sil, yenisini oluştur)
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['avatar'] = $user['avatar'];

                    echo json_encode([
                        "status" => "success",
                        "message" => "Logged in successfully!",
                        "user" => [
                            "id" => $user['id'],
                            "username" => $user['username'],
                            "avatar" => $user['avatar']
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(401);
                    echo json_encode(["status" => "error", "message" => "Invalid username, email, or password."], JSON_UNESCAPED_UNICODE);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "A system error occurred during login."], JSON_UNESCAPED_UNICODE);
            }
            break;

        // --------------------------------------------------------------------------
        // LOGOUT ACTION
        // --------------------------------------------------------------------------
        case 'logout':
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION = [];

            // GÜVENLİK: Çerezi tarayıcıdan tamamen kazı
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    [
                        'expires'  => time() - 42000,
                        'path'     => $params["path"],
                        'domain'   => $params["domain"],
                        'secure'   => $params["secure"],
                        'httponly' => $params["httponly"],
                        'samesite' => $params["samesite"] ?? 'Lax'
                    ]
                );
            }

            session_destroy();

            echo json_encode([
                "status" => "success",
                "message" => "Logged out successfully!"
            ], JSON_UNESCAPED_UNICODE);
            break;

        // --------------------------------------------------------------------------
        // FORGOT PASSWORD
        // --------------------------------------------------------------------------
        case 'forgot':
            $email = trim($input['email'] ?? '');

            if (!$email) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Email is required."
                ]);
                exit;
            }

            $stmt = $db->prepare("
                SELECT id, username 
                FROM users 
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Güvenlik: hesap yoksa da aynı cevap
            if (!$user) {
                echo json_encode([
                    "status" => "success",
                    "message" => "If an account exists, please check your e-mail."
                ]);
                exit;
            }

            $stmt = $db->prepare("
                DELETE FROM password_resets
                WHERE user_id = ?
            ");
            $stmt->execute([$user['id']]);

            $token = bin2hex(random_bytes(32));
            $hash = password_hash($token, PASSWORD_DEFAULT);
            $expires = date("Y-m-d H:i:s", time() + 3600);

            $stmt = $db->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $hash, $expires]);

            $link = "https://capsule.my.to/auth/reset?token=" . $token;

            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = MAIL_HOST;
                $mail->Port = 25;
                $mail->SMTPAuth = true;
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
                $mail->setFrom(MAIL_FROM, "Capsule no-reply");
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = "Capsule Password Reset";
                $mail->Body = "
                <h2>Reset your password</h2>
                <p>Hello {$user['username']},</p>
                <p>Click the button below to reset your password.</p>
                <a href='$link'>Reset Password</a>
                <p>This link expires in 1 hour.</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => $e->getMessage(),
                    "errorInfo" => $mail->ErrorInfo ?? null
                ]);
                exit;
            }

            echo json_encode([
                "status" => "success",
                "message" => "Password reset link has been sent. Please check your e-mail."
            ]);
            break;

        // --------------------------------------------------------------------------
        // RESET PASSWORD
        // --------------------------------------------------------------------------
        case 'reset':
            $token = trim($input['token'] ?? '');
            $password = $input['password'] ?? '';

            if ($token === '' || $password === '') {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Token and password are required."
                ]);
                exit;
            }

            if (strlen($password) < 6) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Password must be at least 6 characters long."
                ]);
                exit;
            }

            // Süresi geçmemiş tüm tokenleri al
            $stmt = $db->prepare("
                SELECT *
                FROM password_resets
                WHERE expires_at > NOW()
            ");
            $stmt->execute();

            $reset = null;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($token, $row['token_hash'])) {
                    $reset = $row;
                    break;
                }
            }

            if (!$reset) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid or expired reset token."
                ]);
                exit;
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Şifreyi güncelle
            $stmt = $db->prepare("
                UPDATE users
                SET password_hash = ?
                WHERE id = ?
            ");
            $stmt->execute([$passwordHash, $reset['user_id']]);

            // Kullanılan tokeni sil
            $stmt = $db->prepare("
                DELETE FROM password_resets
                WHERE id = ?
            ");
            $stmt->execute([$reset['id']]);

            echo json_encode([
                "status" => "success",
                "message" => "Your password has been reset successfully."
            ]);
            break;
    }
    exit;
}

// --- REST-LIKE URL ROUTER RESOLVER ---
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', rtrim($requestUri, '/'));
$resolvedAction = end($uriParts);

$allowedActions = ['login', 'register', 'logout', 'forgot', 'reset', 'detoken'];

if (in_array($resolvedAction, $allowedActions, true)) {
    handleAuthApi($resolvedAction);
} else {
    $inputData = json_decode(file_get_contents("php://input"), true) ?? [];
    $fallbackAction = $_GET['action'] ?? $inputData['action'] ?? $_POST['action'] ?? '';
    if (in_array($fallbackAction, $allowedActions, true)) {
        handleAuthApi($fallbackAction);
    } else {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["status" => "error", "message" => "Invalid endpoints or action missing."], JSON_UNESCAPED_UNICODE);
    }
}