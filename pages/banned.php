<?php
declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login");
    exit;
}

try {
    $db = api_db();
    
    // --- POST ACTION: WARNING / EXPIRED BAN ACKNOWLEDGEMENT ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_terms'])) {
        // Deactivate the user's active restriction or warning record
        $removeBan = $db->prepare("UPDATE user_bans SET is_active = 0 WHERE user_id = ? AND is_active = 1");
        $removeBan->execute([$_SESSION['user_id']]);
        
        // Account reactivated, redirect to home page
        header("Location: /");
        exit;
    }

    // Fetch the most recent active ban/warning record for the user
    // (Even if the duration has expired, we still fetch it because is_active is 1 until they accept terms)
    $stmt = $db->prepare("
        SELECT id, reason, banned_at, expires_at 
        FROM user_bans 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $banInfo = $stmt->fetch();

    // If no active restriction remains, redirect to home page
    if (!$banInfo) {
        header("Location: /");
        exit;
    }

    // Determine restriction type based on the [WARNING] tag set by the admin panel
    $isWarning = (strpos($banInfo['reason'], '[WARNING]') !== false);
    
    // Time check: Has the ban expired or is it still ongoing?
    $isExpired = false;
    if ($banInfo['expires_at'] !== null && strtotime($banInfo['expires_at']) <= time()) {
        $isExpired = true;
    }

    // Can the user proceed immediately? 
    // True if it's just a warning OR if the ban period has already expired.
    $canProceed = $isWarning || $isExpired;

} catch (Exception $e) {
    die("A system error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isWarning ? 'Account Warning' : 'Account Suspended'; ?> - Capsule</title>
    <?php include ROOT_PATH . 'includes/meta.php'; ?>
    <?php include ROOT_PATH . 'includes/icon.php'; ?>
    <link rel="stylesheet" href="/assets/css/Capsule.css">
    
    <style>
        .banned-container {
            max-width: 600px;
            margin: 80px auto;
            padding: 25px;
            background: #fff;
            border: 1px solid #c3c3c3;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
            font-family: "Segoe UI", Arial, sans-serif;
        }
        .banned-header {
            border-bottom: 2px solid <?php echo $isWarning ? '#f0ad4e' : '#d9534f'; ?>;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .banned-header h2 {
            margin: 0;
            color: <?php echo $isWarning ? '#f0ad4e' : '#d9534f'; ?>;
            font-size: 22px;
            font-weight: bold;
        }
        .banned-notice {
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 20px;
        }
        .ban-details-card {
            background: <?php echo $isWarning ? '#fcf8e3' : '#f2dede'; ?>;
            border: 1px solid <?php echo $isWarning ? '#faebcc' : '#ebccd1'; ?>;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .ban-details-card table {
            width: 100%;
            border-collapse: collapse;
        }
        .ban-details-card td {
            padding: 6px 0;
            font-size: 13px;
            vertical-align: top;
        }
        .ban-details-card td.label-cell {
            font-weight: bold;
            color: #555;
            width: 140px;
        }
        .ban-details-card td.value-cell {
            color: #222;
        }
        .terms-activation-box {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            cursor: pointer;
            font-weight: bold;
            color: #333;
        }
        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        .banned-footer {
            border-top: 1px solid #eee;
            padding-top: 15px;
            font-size: 12px;
            color: #666;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-logout {
            background: #555;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 3px;
            text-decoration: none;
        }
        .btn-logout:hover {
            background: #333;
        }
        .btn-submit-active {
            background: #02b757;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 3px;
        }
        .btn-submit-active:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include ROOT_PATH . 'includes/header.php'; ?>

    <div class="main-container">
        <div class="banned-container">
            <div class="banned-header">
                <h2><?php echo $isWarning ? 'Account Warning' : 'Account Suspended'; ?></h2>
            </div>
            
            <div class="banned-notice">
                <?php if ($isWarning): ?>
                    Your account has received an official warning for breaking the community guidelines. You must review the reason below and agree to our Terms of Service to reactivate your account.
                <?php else: ?>
                    Our moderation team has determined that your account behavior has violated Capsule's Terms of Service. As a result, access to the platform has been restricted until the ban expires.
                <?php endif; ?>
            </div>

            <div class="ban-details-card">
                <table>
                    <tr>
                        <td class="label-cell">Action Issued At:</td>
                        <td class="value-cell">
                            <?php echo date('Y-m-d H:i', strtotime($banInfo['banned_at'])); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label-cell">Reason:</td>
                        <td class="value-cell" style="font-weight: bold; color: #d9534f;">
                            <?php echo htmlspecialchars(str_replace('[WARNING] ', '', $banInfo['reason'])); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="label-cell">Status / Expiration:</td>
                        <td class="value-cell">
                            <?php 
                                if ($isWarning) {
                                    echo '<span style="color: #f0ad4e; font-weight: bold;">Warning (Awaiting Review)</span>';
                                } elseif ($banInfo['expires_at'] === null) {
                                    echo '<span style="color: #d9534f; font-weight: bold;">Permanent Ban</span>';
                                } else {
                                    $timeStr = date('Y-m-d H:i', strtotime($banInfo['expires_at']));
                                    if ($isExpired) {
                                        echo "<span style='color: #02b757; font-weight: bold;'>Expired on {$timeStr} (Awaiting Reactivation)</span>";
                                    } else {
                                        echo "<span style='color: #d9534f; font-weight: bold;'>Active Ban until {$timeStr}</span>";
                                    }
                                }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Verification Checkbox & Continue Button Area -->
            <?php if ($canProceed): ?>
                <form method="POST" action="" class="terms-activation-box">
                    <div style="margin-bottom: 12px; font-size: 13px; color: #444;">
                        To regain access to Capsule, you must acknowledge this notice and agree to abide by our policies moving forward.
                    </div>
                    <label class="checkbox-label">
                        <input type="checkbox" id="accept_terms" name="accept_terms" required onchange="toggleSubmitButton(this)">
                        I have read, understood, and agree to abide by the Terms of Service.
                    </label>
                    <div style="margin-top: 15px; text-align: right;">
                        <button type="submit" id="btn_continue" class="btn-submit-active" disabled>Continue</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="terms-activation-box" style="background: #fcf8e3; border-color: #faebcc; color: #8a6d3b; font-size: 13px;">
                    <strong>Restriction Active:</strong> This account is currently suspended. Once your ban execution period concludes, the reactivation panel will automatically unlock here to restore your account.
                </div>
            <?php endif; ?>

            <div class="banned-footer">
                <a href="/api/v1/logout" class="btn-logout" onclick="event.preventDefault(); handleBanLogout();">Log Out</a>
                <span>If you believe this was an error, please contact administration.</span>
            </div>
        </div>
    </div>

    <?php include ROOT_PATH . 'includes/bottom.php'; ?>

    <script>
    function toggleSubmitButton(checkbox) {
        const btn = document.getElementById('btn_continue');
        if (btn) {
            btn.disabled = !checkbox.checked;
        }
    }

    function handleBanLogout() {
        fetch('/api/v1/auth/logout', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = '/auth/login';
                } else {
                    window.location.reload();
                }
            })
            .catch(() => window.location.reload());
    }
    </script>
</body>
</html>