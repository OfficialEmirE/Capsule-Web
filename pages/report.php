<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

// Oturum açılmamışsa giriş sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Farklı URL parametre formatlarını destekleme (user_id, user, game_id, game, ug--game_id vs.)
$targetGameId = $_GET['game_id'] ?? $_GET['game'] ?? $_GET['ug--game_id'] ?? null;
$targetUserId = $_GET['user_id'] ?? $_GET['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Report Abuse - Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/meta.php'; ?>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">

        <style>
            .report-container {
                width: 650px;
                margin: 30px auto;
            }

            .report-box {
                background: var(--panel, #fff);
                border: 1px solid var(--panel-border, #ddd);
                border-radius: 6px;
                padding: 20px;
            }

            .report-box h2 {
                font-size: 18px;
                font-weight: bold;
                border-bottom: 1px solid var(--panel-border, #ddd);
                padding-bottom: 8px;
                margin-bottom: 15px;
                color: var(--text, #333);
            }

            .report-card-summary {
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 4px;
                padding: 10px 12px;
                margin-bottom: 15px;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .report-card-summary strong {
                color: #095fb8;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                display: block;
                font-size: 12px;
                font-weight: bold;
                margin-bottom: 5px;
                color: var(--muted, #555);
            }

            .form-group select,
            .form-group input[type="text"],
            .form-group textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 13px;
                font-family: inherit;
                box-sizing: border-box;
            }

            .form-group textarea {
                min-height: 120px;
                resize: vertical;
            }

            .btn-submit-report {
                background: #e74c3c;
                color: #fff;
                border: none;
                padding: 9px 18px;
                font-size: 13px;
                font-weight: bold;
                border-radius: 4px;
                cursor: pointer;
                transition: background 0.2s;
            }

            .btn-submit-report:hover {
                background: #c0392b;
            }

            .btn-submit-report:disabled {
                background: #bdc3c7;
                cursor: not-allowed;
            }

            .alert-message {
                padding: 10px;
                border-radius: 4px;
                font-size: 13px;
                margin-bottom: 15px;
                display: none;
            }

            .alert-error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .alert-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
        </style>
    </head>
    <body>
        <?php include ROOT_PATH . 'includes/header.php'; ?>

        <div class="report-container">
            <div class="report-box">
                <h2>Report Abuse</h2>

                <div id="alertBox" class="alert-message"></div>

                <form id="reportForm">
                    <!-- Rapor Türü Seçimi -->
                    <div class="form-group">
                        <label for="reportType">What do you want to report?</label>
                        <select id="reportType" name="type" required>
                            <option value="user" <?php echo $targetUserId ? 'selected' : ''; ?>>User</option>
                            <option value="game" <?php echo $targetGameId ? 'selected' : ''; ?>>Game</option>
                        </select>
                    </div>

                    <!-- Hedef ID -->
                    <div class="form-group">
                        <label for="targetId" id="targetIdLabel">Target ID</label>
                        <input type="text" id="targetId" name="target_id" placeholder="Enter ID..." required value="<?php echo htmlspecialchars($targetUserId ?? $targetGameId ?? ''); ?>">
                    </div>

                    <!-- Özet Kartı (JS ile Otomatik Dolar) -->
                    <div id="targetSummary" class="report-card-summary" style="display: none;">
                        <span>Target:</span>
                        <strong id="targetName">Loading...</strong>
                    </div>

                    <!-- Şikayet Nedeni -->
                    <div class="form-group">
                        <label for="reason">Reason</label>
                        <select id="reason" name="reason" required>
                            <option value="" disabled selected>Select a reason...</option>
                            <option value="inappropriate_content">Inappropriate Content / Text</option>
                            <option value="harassment">Harassment or Bullying</option>
                            <option value="spam">Spam / Scam</option>
                            <option value="impersonation">Impersonation</option>
                            <option value="copyright">Copyright Violation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Detay / Açıklama (Zorunlu Değil) -->
                    <div class="form-group">
                        <label for="details">Additional Details <span style="font-weight: normal; color: #888;">(Optional)</span></label>
                        <textarea id="details" name="details" placeholder="Please describe the issue in detail (optional)..." maxlength="1000"></textarea>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="javascript:history.back()" style="font-size: 12px; color: #555;">&larr; Go Back</a>
                        <button type="submit" id="submitBtn" class="btn-submit-report">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>

        <?php include ROOT_PATH . 'includes/bottom.php'; ?>
        <!-- report.php dosyasındaki <script> bloğunu bununla değiştir: -->
        <script>
        (function() {
            var reportTypeSelect = document.getElementById('reportType');
            var targetIdInput = document.getElementById('targetId');
            var targetIdLabel = document.getElementById('targetIdLabel');
            var targetSummary = document.getElementById('targetSummary');
            var targetName = document.getElementById('targetName');
            var reportForm = document.getElementById('reportForm');
            var submitBtn = document.getElementById('submitBtn');
            var alertBox = document.getElementById('alertBox');

            function updateLabels() {
                var type = reportTypeSelect.value;
                if (type === 'user') {
                    targetIdLabel.textContent = "User ID";
                } else if (type === 'game') {
                    targetIdLabel.textContent = "Game ID";
                }
                fetchTargetDetails();
            }

            function fetchTargetDetails() {
                var type = reportTypeSelect.value;
                var id = targetIdInput.value.trim();

                if (!id) {
                    targetSummary.style.display = 'none';
                    return;
                }

                targetSummary.style.display = 'flex';
                targetName.textContent = "Validating target...";

                var endpoint = type === 'user' 
                    ? '/api/v1/users/info?id=' + encodeURIComponent(id)
                    : '/api/v1/games/info?id=' + encodeURIComponent(id);

                fetch(endpoint)
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data && data.status === 'success') {
                            if (type === 'user' && data.user) {
                                targetName.textContent = "User: " + data.user.username + " (#" + data.user.id + ")";
                            } else if (type === 'game' && data.game) {
                                targetName.textContent = "Game: " + data.game.name + " (#" + data.game.id + ")";
                            } else {
                                targetName.textContent = "Target found (#" + id + ")";
                            }
                        } else {
                            targetName.textContent = "Target not found (ID: " + id + ")";
                        }
                    })
                    .catch(function() {
                        targetName.textContent = "Target ID: #" + id;
                    });
            }

            reportTypeSelect.addEventListener('change', updateLabels);
            targetIdInput.addEventListener('input', fetchTargetDetails);

            updateLabels();

            reportForm.addEventListener('submit', function(e) {
                e.preventDefault();

                showAlert('', '');
                submitBtn.disabled = true;
                submitBtn.textContent = "Submitting...";

                var selectedType = reportTypeSelect.value.toLowerCase().trim();

                var payload = {
                    target_type: selectedType,
                    target_id: parseInt(targetIdInput.value, 10),
                    reason: document.getElementById('reason').value,
                    details: document.getElementById('details').value
                };

                fetch('/api/v1/reporter/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(function(res) {
                    return res.json().then(function(data) {
                        if (!res.ok) throw new Error(data.message || "Failed to submit report.");
                        return data;
                    });
                })
                .then(function(data) {
                    showAlert('success', 'Thank you. Your report has been submitted successfully.');
                    reportForm.reset();
                    targetSummary.style.display = 'none';
                })
                .catch(function(err) {
                    showAlert('error', err.message || 'An error occurred while sending the report.');
                })
                .finally(function() {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Submit Report";
                });
            });

            function showAlert(type, message) {
                if (!message) {
                    alertBox.style.display = 'none';
                    return;
                }
                alertBox.className = 'alert-message ' + (type === 'success' ? 'alert-success' : 'alert-error');
                alertBox.textContent = message;
                alertBox.style.display = 'block';
            }
        })();
        </script>
    </body>
</html>
