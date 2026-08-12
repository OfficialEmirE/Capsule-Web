<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Download - Capsule</title>
        <?php include ROOT_PATH . 'includes/meta.php'; ?>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">
    </head>
    <body>
        <?php include ROOT_PATH . 'includes/header.php'; ?>
        <div class="main-container">
            <div class="top-row">
                <div class="featured-game">
                    <div style="width:600px;">
                        <h2>Download Capsule</h2>
                        <p>Choose your platform to download the Capsule Launcher.</p>
                        <div class="download-btn">
                            <label for="launcherPlatform"><b>Platform</b></label>
                            <select id="launcherPlatform" style="display:block;width:100%;margin:8px 0;padding:8px;border:1px solid #ccc;border-radius:3px;">
                                <option value="windows">Windows</option>
                                <!--<option value="mac">macOS</option>
                                <option value="linux">Linux</option>-->
                            </select>
                            <a id="launcherDownload" href="https://raw.githubusercontent.com/OfficialEmirE/ramazangay/main/CapsuleLauncher.exe" class="btn-download">Download for Windows</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include ROOT_PATH . 'includes/bottom.php'; ?>
        <script>
        (function () {
            var platform = document.getElementById('launcherPlatform');
            var download = document.getElementById('launcherDownload');
            var files = {
                windows: { url: 'https://raw.githubusercontent.com/OfficialEmirE/ramazangay/main/CapsuleLauncher.exe', label: 'Download for Windows' },
                mac: { url: 'https://raw.githubusercontent.com/OfficialEmirE/ramazangay/main/CapsuleLauncher.dmg', label: 'Download for macOS' },
                linux: { url: 'https://raw.githubusercontent.com/OfficialEmirE/ramazangay/main/CapsuleLauncher.AppImage', label: 'Download for Linux' }
            };
            platform.addEventListener('change', function () {
                var selected = files[platform.value] || files.windows;
                download.href = selected.url;
                download.textContent = selected.label;
            });
        })();
        </script>
    </body>
</html>
