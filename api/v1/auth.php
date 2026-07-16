<?php
declare(strict_types=1);

// Config dosyasını dahil ediyoruz. 2 seviye yukarı çıkarak ana dizindeki config.php'yi arar.
$configPath = dirname(__DIR__, 1) . '/config.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["status" => "error", "message" => "Configuration file not found."], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $configPath;

/**
 * Handles the Auth API requests.
 * Since this is included dynamically via your router (index.php),
 * we define it as a callable function.
 * 
 * @param string $action 'login', 'register' or 'logout'
 */
function handleAuthApi(string $action): void
{
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Only POST requests are allowed."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Get database connection from config.php's api_db() function (Not needed for logout but kept for standard flow)
    $db = null;
    if ($action !== 'logout') {
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
                $colors = ['#3498db', '#2ecc71', '#9b59b6', '#e67e22', '#e74c3c', '#1abc9c', '#FFFFFF',];
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

            // Session verilerini temizle ve oturumu yok et
            $_SESSION = [];

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }

            session_destroy();

            echo json_encode([
                "status" => "success",
                "message" => "Logged out successfully!"
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid action. Choose register, login or logout."], JSON_UNESCAPED_UNICODE);
            break;
    }
    exit;
}

// --- REST-LIKE URL ROUTER RESOLVER ---
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', rtrim($requestUri, '/'));
$resolvedAction = end($uriParts);

if (in_array($resolvedAction, ['login', 'register', 'logout'], true)) {
    handleAuthApi($resolvedAction);
} else {
    $fallbackAction = $_GET['action'] ?? $input['action'] ?? '';
    if (in_array($fallbackAction, ['login', 'register', 'logout'], true)) {
        handleAuthApi($fallbackAction);
    } else {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["status" => "error", "message" => "Invalid endpoints or action missing."], JSON_UNESCAPED_UNICODE);
    }
}