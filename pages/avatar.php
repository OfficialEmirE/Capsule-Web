<?php
declare(strict_types=1);

// Start output buffering
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

// Get the authenticated user's ID
$loggedInUserId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

// Redirect to login if the user is not authenticated
if (!$loggedInUserId) {
    header("Location: /auth/login?next=/avatar", true, 302);
    ob_end_flush();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Avatar - Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/meta.php'; ?>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">
        <style>
            .avatar-editor-container {
                width: 970px;
                margin: 20px auto;
                display: grid;
                grid-template-columns: 300px 650px;
                gap: 20px;
            }

            /* Left Panel: Real-time Live Preview */
            .preview-sidebar {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .preview-card {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                border-radius: 6px;
                padding: 20px;
                text-align: center;
                box-shadow: inset -1px -1px 0px var(--panel-border);
            }

            .avatar-svg-container {
                width: 150px;
                height: 150px;
                margin: 0 auto 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                border: 1px dashed var(--panel-border);
                border-radius: 6px;
                padding: 10px;
            }

            .avatar-svg-container svg {
                width: 100%;
                height: 100%;
                display: block;
            }

            .color-code-display {
                font-family: monospace;
                font-size: 14px;
                color: var(--text);
                background: var(--panel-border);
                padding: 4px 10px;
                border-radius: 4px;
                display: inline-block;
                margin-top: 5px;
            }

            /* Right Panel: Editor Area */
            .editor-main-content {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                border-radius: 6px;
                padding: 25px;
            }

            .editor-title {
                font-size: 18px;
                font-weight: bold;
                color: var(--text);
                border-bottom: 1px solid var(--panel-border);
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            .section-label {
                font-weight: bold;
                display: block;
                margin-bottom: 12px;
                font-size: 13px;
                color: var(--text);
            }

            /* Retro Color Palette Buttons */
            .preset-colors {
                display: flex;
                gap: 12px;
                margin-bottom: 25px;
                flex-wrap: wrap;
            }

            .color-btn {
                width: 40px;
                height: 40px;
                border-radius: 4px;
                border: 2px solid #333;
                cursor: pointer;
                box-shadow: 1px 1px 0px #fff;
                transition: transform 0.1s ease;
            }

            .color-btn:hover {
                transform: scale(1.1);
            }

            .custom-picker-wrapper {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 30px;
            }

            .custom-color-input {
                width: 60px;
                height: 40px;
                border: 1px solid var(--panel-border);
                border-radius: 4px;
                cursor: pointer;
                background: none;
                padding: 0;
            }

            .save-avatar-btn {
                background: #2ecc71;
                color: white;
                border: none;
                padding: 10px 24px;
                font-size: 13px;
                font-weight: bold;
                border-radius: 4px;
                cursor: pointer;
                box-shadow: inset -1px -1px 0px #27ae60;
                transition: background 0.2s;
            }

            .save-avatar-btn:hover {
                background: #27ae60;
            }

            .save-avatar-btn:disabled {
                background: #95a5a6;
                cursor: not-allowed;
            }

            .status-msg {
                margin-top: 15px;
                font-size: 13px;
                font-weight: bold;
                display: none;
            }
        </style>
    </head>
    <body>
        <?php include ROOT_PATH . 'includes/header.php'; ?>

        <div class="avatar-editor-container">
            
            <div class="preview-sidebar">
                <div class="preview-card">
                    <div id="avatarContainer" class="avatar-svg-container">
                        <span style="color: var(--muted); font-size: 12px;">Loading...</span>
                    </div>
                    <div>
                        <div class="color-code-display" id="colorCode">#FFFFFF</div>
                    </div>
                </div>
            </div>

            <div class="editor-main-content">
                <div class="editor-title">Customize Character Color</div>

                <span class="section-label">Preset Color Palette:</span>
                <div class="preset-colors">
                    <button class="color-btn" style="background-color: #3498db;" data-color="#3498db" title="Peter River"></button>
                    <button class="color-btn" style="background-color: #2ecc71;" data-color="#2ecc71" title="Emerald"></button>
                    <button class="color-btn" style="background-color: #9b59b6;" data-color="#9b59b6" title="Amethyst"></button>
                    <button class="color-btn" style="background-color: #e67e22;" data-color="#e67e22" title="Carrot"></button>
                    <button class="color-btn" style="background-color: #e74c3c;" data-color="#e74c3c" title="Alizarin"></button>
                    <button class="color-btn" style="background-color: #1abc9c;" data-color="#1abc9c" title="Turquoise"></button>
                    <button class="color-btn" style="background-color: #FFFFFF;" data-color="#FFFFFF" title="White"></button>
                </div>

                <span class="section-label">Choose a Custom Color:</span>
                <div class="custom-picker-wrapper">
                    <input type="color" id="avatarColorInput" class="custom-color-input" value="#ffffff">
                    <span style="font-size: 12px; color: var(--muted);">Click the palette buttons or use the color picker for precise adjustments.</span>
                </div>

                <div style="border-top: 1px solid var(--panel-border); padding-top: 20px;">
                    <button class="save-avatar-btn" id="saveAvatarBtn">Save Changes</button>
                    <div id="statusMsg" class="status-msg"></div>
                </div>
            </div>

        </div>

        <?php include ROOT_PATH . 'includes/bottom.php'; ?>

        <script>
        (function() {
            var loggedInUserId = <?php echo json_encode((string)$loggedInUserId); ?>;
            var currentSVGDoc = null;
            var activeColor = "#ffffff";

            var container = document.getElementById('avatarContainer');
            var colorCode = document.getElementById('colorCode');
            var colorInput = document.getElementById('avatarColorInput');
            var presetBtns = document.querySelectorAll('.color-btn');
            var saveBtn = document.getElementById('saveAvatarBtn');
            var statusMsg = document.getElementById('statusMsg');

            fetch('/api/v1/users/avatar?id=' + encodeURIComponent(loggedInUserId))
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.status === 'success' && data.avatar) {
                        activeColor = data.avatar;
                    }
                    loadAndRenderSVG();
                })
                .catch(function(err) {
                    console.error("Could not load current color:", err);
                    loadAndRenderSVG();
                });

            function loadAndRenderSVG() {
                fetch('/assets/images/body.svg')
                    .then(function(res) { return res.text(); })
                    .then(function(svgText) {
                        var parser = new DOMParser();
                        currentSVGDoc = parser.parseFromString(svgText, "image/svg+xml");
                        updateColors(activeColor);
                    })
                    .catch(function(err) {
                        container.innerHTML = '<span style="color: red; font-size: 12px;">Preview could not be loaded.</span>';
                        console.error("SVG load error: ", err);
                    });
            }

            function updateColors(targetColor) {
                if (!currentSVGDoc) return;

                var svgElement = currentSVGDoc.querySelector('svg').cloneNode(true);
                var paths = svgElement.querySelectorAll('path');

                paths.forEach(function(path) {
                    var fill = (path.getAttribute('fill') || '').toLowerCase();
                    var stroke = (path.getAttribute('stroke') || '').toLowerCase();

                    if (
                        fill === '#ffffff' ||
                        fill === '#fff' ||
                        stroke === '#ffffff' ||
                        stroke === '#fff'
                    ) {
                        path.setAttribute('fill', targetColor);
                        path.setAttribute('stroke', targetColor);
                    }
                });

                container.innerHTML = '';
                container.appendChild(svgElement);
                
                colorCode.textContent = targetColor.toUpperCase();
                colorInput.value = targetColor;
                activeColor = targetColor;
            }

            presetBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var color = btn.getAttribute('data-color');
                    updateColors(color);
                });
            });

            colorInput.addEventListener('input', function(e) {
                updateColors(e.target.value);
            });

            saveBtn.addEventListener('click', function() {
                saveBtn.disabled = true;
                saveBtn.textContent = "Saving...";
                statusMsg.style.display = "none";

                fetch('/api/v1/users/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: parseInt(loggedInUserId, 10),
                        avatar: activeColor
                    })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = "Save Changes";
                    statusMsg.style.display = "block";

                    if (data && data.status === 'success') {
                        statusMsg.style.color = "#2ecc71";
                        statusMsg.textContent = "✓ Your avatar color has been successfully updated!";
                    } else {
                        statusMsg.style.color = "#e74c3c";
                        statusMsg.textContent = "❌ Update failed: " + (data.message || "Unknown error.");
                    }
                })
                .catch(function(err) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = "Save Changes";
                    statusMsg.style.display = "block";
                    statusMsg.style.color = "#e74c3c";
                    statusMsg.textContent = "❌ Network connection error occurred.";
                    console.error("API Error: ", err);
                });
            });
        })();
        </script>
    </body>
</html>