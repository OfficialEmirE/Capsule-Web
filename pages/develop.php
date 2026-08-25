<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login');
    exit;
}

$developerId = (int)$_SESSION['user_id'];
$developerGames = [];

try {
    $developerDb = api_db();
    
    // Tablonuzdaki sütun isimlerini kontrol edin. desc reserved keyword olduğu için backtick (`) içindedir.
    $developerStmt = $developerDb->prepare('SELECT id, name, `desc`, max_players, public, thumbnail_urls, created_at, updated_at FROM games WHERE ownerUserId = ? ORDER BY id DESC');
    $developerStmt->execute([$developerId]);
    $developerGames = $developerStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    // Hata durumunda boş dönmek yerine loglayabilirsiniz: error_log($e->getMessage());
    $developerGames = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Develop - Capsule Beta</title>
    <?php include ROOT_PATH . 'includes/meta.php'; ?>
    <?php include ROOT_PATH . 'includes/icon.php'; ?>
    <link rel="stylesheet" href="/assets/css/Capsule.css">
    <style>
        .develop-page { width:970px; margin:20px auto 30px; }
        .develop-header { margin-bottom:15px; }
        .develop-header h1 { margin:0 0 4px; font-size:25px; }
        .develop-header p { margin:0; color:var(--muted); font-size:12px; }
        .develop-grid { display:grid; grid-template-columns:minmax(0,1fr); gap:15px; align-items:start; }
        .develop-panel { background:var(--panel); border:1px solid var(--panel-border); border-radius:6px; padding:15px; }
        .develop-panel h2 { margin:0 0 12px; padding-bottom:8px; border-bottom:1px solid var(--panel-border); font-size:16px; }
        .panel-heading { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:-3px; margin-bottom:12px; }
        .panel-heading h2 { margin:0; border:0; padding:0; }
        .form-group { margin-bottom:11px; }
        .form-group label { display:block; margin-bottom:4px; font-size:12px; font-weight:bold; color:var(--muted); }
        .develop-input, .develop-textarea, .develop-select { width:100%; padding:7px; border:1px solid #ccc; border-radius:3px; font:inherit; font-size:12px; background:#fff; color:var(--text); }
        .develop-input[readonly] { background:#f1f1f1; color:#777; cursor:not-allowed; }
        .develop-textarea { min-height:105px; resize:vertical; }
        .develop-button { border:0; border-radius:3px; padding:8px 13px; color:#fff; background:var(--button-primary); cursor:pointer; font-size:12px; font-weight:bold; }
        .develop-button:hover { background:var(--button-primary-hover); }
        .develop-button.secondary { background:#666; }
        .develop-button.green { background:#4a9b4a; }
        .develop-button.open-studio-game, #createOpenStudio { background:#8e44ad; }
        .develop-button.open-studio-game:hover, #createOpenStudio:hover { background:#71368a; }
        .develop-button:disabled { opacity:.6; cursor:wait; }
        .game-editor { border:1px solid var(--panel-border); border-radius:5px; padding:13px; margin-bottom:10px; }
        .game-editor:last-child { margin-bottom:0; }
        .game-editor-heading { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:0; }
        .game-editor.editing .game-editor-heading { margin-bottom:10px; }
        .game-editor-heading h3 { margin:0; font-size:15px; display:flex; align-items:center; gap:9px; }
        .game-editor-thumb { width:96px; aspect-ratio:16 / 9; object-fit:cover; background:#ddd; border:1px solid #cfcfcf; border-radius:3px; }
        .current-game-images { display:flex; flex-wrap:wrap; gap:8px; min-height:42px; }
        .current-game-image { width:112px; }
        .current-game-images img { display:block; width:112px; aspect-ratio:16 / 9; object-fit:cover; background:#ddd; border:1px solid #cfcfcf; border-radius:3px; }
        .current-game-image.marked-remove img { opacity:.35; }
        .remove-image { width:100%; margin-top:3px; padding:3px 4px; border:1px solid #c0392b; border-radius:3px; background:#e74c3c; color:#fff; font-size:10px; cursor:pointer; }
        .remove-image.restore-image { background:#666; border-color:#555; }
        .current-game-images .no-images { color:#777; font-size:11px; padding:10px 0; }
        .game-editor-actions { display:flex; gap:6px; }
        .game-editor-actions button { padding:5px 9px; font-size:11px; }
        .game-editor-actions .edit-game { padding:8px 16px; font-size:13px; }
        .game-editor:not(.editing) .editor-fields, .game-editor:not(.editing) .game-meta { display:none; }
        .game-editor:not(.editing) .save-game { display:none; }
        .game-editor:not(.editing) .cancel-game { display:none; }
        .game-editor:not(.editing) .open-studio-game { display:none; }
        #createGamePanel { margin-top:-5px; }
        .editor-fields { display:grid; grid-template-columns:1fr 150px 110px; gap:10px; }
        .editor-fields .wide { grid-column:1 / -1; }
        .game-meta { margin-top:8px; color:var(--muted); font-size:11px; }
        .game-engagement-meta { display:flex; gap:12px; margin-top:6px; color:var(--muted); font-size:11px; }
        .game-engagement-meta strong { color:var(--text); }
        .game-like-count { color:#348b48 !important; }
        .game-dislike-count { color:#c0392b !important; }
        .empty-games { padding:30px 10px; text-align:center; color:var(--muted); font-size:12px; }
        .develop-message { display:none; padding:9px; margin-bottom:12px; border-radius:4px; font-size:12px; font-weight:bold; }
        .develop-message.success { display:block; background:#dff0d8; color:#3c763d; }
        .develop-message.error { display:block; background:#f2dede; color:#a94442; }
        @media (max-width:990px) { .navbar,.footer{min-width:0;} .navbar-inner,.footer-inner,.develop-page{width:calc(100% - 24px);} }
        @media (max-width:700px) { .develop-grid{grid-template-columns:1fr;} .editor-fields{grid-template-columns:1fr;} }
    </style>
</head>
<body>
<?php include ROOT_PATH . 'includes/header.php'; ?>

<main class="develop-page">
    <div class="develop-header">
        <h1>Develop</h1>
        <p>Create and manage your Capsule games.</p>
    </div>

    <div id="developMessage" class="develop-message"></div>

    <div class="develop-grid">
        <section id="createGamePanel" class="develop-panel" style="display:none;">
            <div class="panel-heading">
                <h2>Create Game</h2>
                <div class="game-editor-actions">
                    <button id="createOpenStudio" class="develop-button secondary" type="button">Open Studio</button>
                    <button id="backToMyGames" class="develop-button secondary" type="button">My Games</button>
                </div>
            </div>
            <form id="createGameForm">
                <div class="form-group">
                    <label for="newGameName">Name</label>
                    <input id="newGameName" class="develop-input" name="name" maxlength="255" required>
                </div>
                <div class="form-group">
                    <label for="newGameDescription">Description</label>
                    <textarea id="newGameDescription" class="develop-textarea" name="desc"></textarea>
                </div>
                <div class="form-group">
                    <label for="newGamePlayers">Max Players</label>
                    <input id="newGamePlayers" class="develop-input" type="number" name="max_players" min="1" max="1000" value="12" required>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="public" value="1" checked> Public game</label>
                </div>
                <div class="form-group">
                    <label for="newGameImages">Images</label>
                    <input id="newGameImages" class="develop-input game-image-input" name="images" type="file" accept="image/*" multiple>
                    <small style="color:#777;">Maximum 5 images per game.</small>
                </div>
                <div class="form-group">
                    <label for="newGameDwf">DWF file</label>
                    <input id="newGameDwf" class="develop-input" name="dwf_file" type="file" accept=".dwf,model/vnd.dwf,application/octet-stream">
                    <small style="color:#777;">DWF means DikenEngine World File.</small>
                </div>
                <div class="form-group">
                    <label for="newGameVideo">Video URL (optional)</label>
                    <input id="newGameVideo" class="develop-input" name="video_url" type="url" placeholder="YouTube video URL">
                    <small style="color:#777;">Only one video can be attached.</small>
                </div>
                <button class="develop-button green" type="submit">Create Game</button>
            </form>
        </section>

        <section id="myGamesPanel" class="develop-panel">
            <div class="panel-heading">
                <h2>My Games</h2>
                <button id="showCreateGame" class="develop-button green" type="button">Create Game</button>
            </div>
            <div id="myGames">
                <?php if (empty($developerGames)): ?>
                    <div class="empty-games">You have not created any games yet.</div>
                <?php else: ?>
                    <?php foreach ($developerGames as $game): ?>
                        <?php
                        $gameMedia = json_decode((string)($game['thumbnail_urls'] ?? ''), true);
                        $videoAssetId = 0;
                        $dwfAssetId = 0;
                        $imageAssetId = 0;
                        $imageAssetIds = [];

                        if (is_array($gameMedia)) {
                            foreach ($gameMedia as $gameAsset) {
                                if (!is_array($gameAsset)) continue;
                                $type = strtolower((string)($gameAsset['type'] ?? ''));
                                $assetId = (int)($gameAsset['id'] ?? 0);

                                if ($type === 'video' && !$videoAssetId) {
                                    $videoAssetId = $assetId;
                                } elseif ($type === 'dwf' && !$dwfAssetId) {
                                    $dwfAssetId = $assetId;
                                } elseif ($type === 'image' && $assetId > 0) {
                                    if (!$imageAssetId) $imageAssetId = $assetId;
                                    $imageAssetIds[] = $assetId;
                                }
                            }
                        }
                        ?>
                        <form class="game-editor" data-game-id="<?php echo (int)$game['id']; ?>" data-dwf-id="<?php echo $dwfAssetId; ?>" data-image-assets="<?php echo htmlspecialchars(json_encode($imageAssetIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>" data-existing-media="<?php echo htmlspecialchars(json_encode(is_array($gameMedia) ? $gameMedia : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="game-editor-heading">
                                <h3><img class="game-editor-thumb" data-asset-id="<?php echo $imageAssetId; ?>" src="/assets/images/capsuleTemplate.png" alt=""><span><?php echo htmlspecialchars($game['name'] ?? ''); ?></span></h3>
                                <div class="game-editor-actions">
                                    <button class="develop-button secondary edit-game" type="button">Edit</button>
                                    <button class="develop-button open-studio-game" type="button" style="<?php echo $dwfAssetId ? '' : 'display:none;'; ?>">Open Studio</button>
                                    <button class="develop-button secondary cancel-game" type="button">Cancel</button>
                                    <button class="develop-button green save-game" type="submit">Save</button>
                                </div>
                            </div>
                            <div class="editor-fields">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input class="develop-input game-name" maxlength="255" value="<?php echo htmlspecialchars($game['name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Max Players</label>
                                    <input class="develop-input game-players" type="number" min="1" max="1000" value="<?php echo (int)($game['max_players'] ?? 12); ?>" readonly title="Max Players editing is temporarily disabled.">
                                </div>
                                <div class="form-group">
                                    <label>Visibility</label>
                                    <select class="develop-select game-public">
                                        <option value="1" <?php echo (int)($game['public'] ?? 1) === 1 ? 'selected' : ''; ?>>Public</option>
                                        <option value="0" <?php echo (int)($game['public'] ?? 1) === 0 ? 'selected' : ''; ?>>Private</option>
                                    </select>
                                </div>
                                <div class="form-group wide">
                                    <label>Description</label>
                                    <textarea class="develop-textarea game-description"><?php echo htmlspecialchars($game['desc'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group wide">
                                    <label>Images</label>
                                    <div class="current-game-images"></div>
                                    <small style="color:#777;">Current game images. Maximum 5 images per game.</small>
                                </div>
                                <div class="form-group wide">
                                    <label>New Images</label>
                                    <input class="develop-input game-images game-image-input" type="file" accept="image/*" multiple>
                                    <small style="color:#777;">New images are added to the game.</small>
                                </div>
                                <div class="form-group wide">
                                    <label>New DWF File</label>
                                    <input class="develop-input game-dwf" type="file" accept=".dwf,model/vnd.dwf,application/octet-stream">
                                    <small style="color:#777;">DWF means DikenEngine World File.</small>
                                </div>
                                <div class="form-group wide">
                                    <label>Replace Video (optional)</label>
                                    <input class="develop-input game-video" type="url" data-asset-id="<?php echo $videoAssetId; ?>" placeholder="YouTube video URL">
                                    <small style="color:#777;">Leave empty to remove the current video.</small>
                                </div>
                            </div>
                            <div class="game-meta">Created: <?php echo htmlspecialchars((string)($game['created_at'] ?? '')); ?> · Updated: <?php echo htmlspecialchars((string)($game['updated_at'] ?? '')); ?></div>
                            <div class="game-engagement-meta"><span>Likes: <strong class="game-like-count">0</strong></span><span>Dislikes: <strong class="game-dislike-count">0</strong></span></div>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php include ROOT_PATH . 'includes/bottom.php'; ?>
<script>
(function () {
    var message = document.getElementById('developMessage');

    function showMessage(text, type) {
        message.textContent = text;
        message.className = 'develop-message ' + type;
    }

    function sendGame(endpoint, payload, button) {
        button.disabled = true;
        return fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || data.status !== 'success') throw new Error(data.message || 'Request failed.');
                return data;
            });
        }).finally(function () {
            button.disabled = false;
        });
    }

    function uploadVideo(url) {
        var form = new FormData();
        form.append('type', 'video');
        form.append('url', url);
        return fetch('/api/v1/assets/upload', { method: 'POST', body: form })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || data.status !== 'success' || !data.asset) throw new Error(data.message || 'Video asset upload failed.');
                    return data.asset;
                });
            });
    }

    function uploadFile(file, type) {
        var form = new FormData();
        form.append('type', type);
        form.append('file', file);
        return fetch('/api/v1/assets/upload', { method: 'POST', body: form })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || data.status !== 'success' || !data.asset) throw new Error(data.message || 'Asset upload failed.');
                    return data.asset;
                });
            });
    }

    function uploadFiles(input, type) {
        var files = input && input.files ? Array.from(input.files) : [];
        return Promise.all(files.map(function (file) { return uploadFile(file, type); }));
    }

    function mediaEntry(asset) {
        return { type: String(asset.type), id: Number(asset.id) };
    }

    function parseExistingMedia(form) {
        try {
            var media = JSON.parse(form.getAttribute('data-existing-media') || '[]');
            return Array.isArray(media) ? media.filter(function (item) { return item && item.type && item.id; }) : [];
        } catch (error) {
            return [];
        }
    }

    function withoutType(media, type) {
        return media.filter(function (item) { return String(item.type).toLowerCase() !== type; });
    }

    var createPanel = document.getElementById('createGamePanel');
    var myGamesPanel = document.getElementById('myGamesPanel');
    var showCreateButton = document.getElementById('showCreateGame');
    var backToMyGames = document.getElementById('backToMyGames');

    function showPanel(panel) {
        var showCreate = panel === createPanel;
        createPanel.style.display = showCreate ? 'block' : 'none';
        myGamesPanel.style.display = showCreate ? 'none' : 'block';
    }

    if (showCreateButton && createPanel && myGamesPanel) {
        showCreateButton.addEventListener('click', function () { showPanel(createPanel); });
    }
    if (backToMyGames && createPanel && myGamesPanel) {
        backToMyGames.addEventListener('click', function () { showPanel(myGamesPanel); });
    }
    var createOpenStudio = document.getElementById('createOpenStudio');
    if (createOpenStudio) {
        createOpenStudio.addEventListener('click', function () { 
            if (typeof openCapsuleLauncher === 'function') openCapsuleLauncher(0, true); 
        });
    }

    var engagementIds = Array.from(document.querySelectorAll('.game-editor[data-game-id]')).map(function (form) {
        return form.getAttribute('data-game-id');
    }).filter(Boolean);
    if (engagementIds.length) {
        fetch('/api/v1/games/engagement?ids=' + encodeURIComponent(engagementIds.join(',')), { headers: { Accept: 'application/json' } })
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (data) {
                var all = data && data.status === 'success' ? data.engagements : null;
                if (!all) return;
                document.querySelectorAll('.game-editor[data-game-id]').forEach(function (form) {
                    var counts = all[String(form.getAttribute('data-game-id'))] || { likes: 0, dislikes: 0 };
                    var like = form.querySelector('.game-like-count');
                    var dislike = form.querySelector('.game-dislike-count');
                    if (like) like.textContent = Number(counts.likes || 0).toLocaleString();
                    if (dislike) dislike.textContent = Number(counts.dislikes || 0).toLocaleString();
                });
            }).catch(function () {});
    }

    document.querySelectorAll('.game-video[data-asset-id]').forEach(function (input) {
        var assetId = Number(input.getAttribute('data-asset-id'));
        if (!assetId) return;

        fetch('/api/v1/assets?id=' + encodeURIComponent(assetId) + '&type=video', {
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).then(function (data) {
            var asset = data && data.status === 'success' ? data.asset : null;
            if (asset && asset.url) input.value = asset.url;
        }).catch(function () {});
    });

    document.querySelectorAll('.game-editor-thumb[data-asset-id]').forEach(function (image) {
        var assetId = Number(image.getAttribute('data-asset-id'));
        if (!assetId) return;

        fetch('/api/v1/assets?id=' + encodeURIComponent(assetId) + '&type=image', {
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).then(function (data) {
            var asset = data && data.status === 'success' ? data.asset : null;
            if (asset && asset.url) image.src = asset.url;
        }).catch(function () {});
    });

    var imageAssetIds = [];
    document.querySelectorAll('.game-editor[data-image-assets]').forEach(function (form) {
        try {
            var ids = JSON.parse(form.getAttribute('data-image-assets') || '[]');
            if (Array.isArray(ids)) imageAssetIds = imageAssetIds.concat(ids.map(Number).filter(Boolean));
        } catch (error) {}
    });
    imageAssetIds = imageAssetIds.filter(function (id, index, all) { return all.indexOf(id) === index; });

    if (imageAssetIds.length) {
        fetch('/api/v1/assets?ids=' + encodeURIComponent(imageAssetIds.join(',')) + '&type=image', {
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).then(function (data) {
            var assets = data && data.status === 'success' && Array.isArray(data.assets) ? data.assets : [];
            var byId = {};
            assets.forEach(function (asset) { byId[Number(asset.id)] = asset; });

            document.querySelectorAll('.game-editor[data-image-assets]').forEach(function (form) {
                var container = form.querySelector('.current-game-images');
                if (!container) return;
                var ids = [];
                try { ids = JSON.parse(form.getAttribute('data-image-assets') || '[]'); } catch (error) {}
                var images = ids.map(function (id) { return byId[Number(id)]; }).filter(function (asset) { return asset && asset.url; });
                container.innerHTML = images.length
                    ? images.map(function (asset) {
                        var safeUrl = String(asset.url).replace(/"/g, '&quot;');
                        return '<div class="current-game-image" data-asset-id="' + Number(asset.id) + '"><img src="' + safeUrl + '" alt="Game image"><button class="remove-image" type="button">Remove</button></div>';
                    }).join('')
                    : '<span class="no-images">No images uploaded.</span>';

                container.querySelectorAll('.remove-image').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var image = button.closest('.current-game-image');
                        var removing = image.classList.toggle('marked-remove');
                        button.classList.toggle('restore-image', removing);
                        button.textContent = removing ? 'Restore' : 'Remove';
                    });
                });
            });
        }).catch(function () {});
    } else {
        document.querySelectorAll('.current-game-images').forEach(function (container) {
            container.innerHTML = '<span class="no-images">No images uploaded.</span>';
        });
    }

    document.querySelectorAll('.game-image-input').forEach(function (input) {
        input.addEventListener('change', function () {
            if (input.files.length > 5) {
                input.value = '';
                showMessage('You can select a maximum of 5 images.', 'error');
            }
        });
    });

    document.getElementById('createGameForm').addEventListener('submit', function (event) {
        event.preventDefault();
        var form = event.currentTarget;
        var button = form.querySelector('button[type="submit"]');
        var data = new FormData(form);
        var selectedImages = form.querySelector('#newGameImages');
        if (selectedImages && selectedImages.files.length > 5) {
            showMessage('You can select a maximum of 5 images.', 'error');
            return;
        }

        var imageUploads = uploadFiles(selectedImages, 'image');
        var dwfInput = form.querySelector('#newGameDwf');
        var dwfUpload = dwfInput && dwfInput.files.length ? uploadFile(dwfInput.files[0], 'dwf') : Promise.resolve(null);
        var videoUrl = String(data.get('video_url') || '').trim();
        var videoAsset = videoUrl ? uploadVideo(videoUrl) : Promise.resolve(null);

        Promise.all([imageUploads, dwfUpload, videoAsset]).then(function (uploads) {
            var media = uploads[0].map(mediaEntry);
            if (uploads[1]) media.push(mediaEntry(uploads[1]));
            if (uploads[2]) media.push(mediaEntry(uploads[2]));
            return sendGame('/api/v1/games/create', {
                name: data.get('name'),
                desc: data.get('desc'),
                max_players: Number(data.get('max_players')),
                public: data.get('public') === '1' ? 1 : 0,
                thumbnail_urls: media
            }, button);
        }).then(function () {
            showMessage('Game created successfully. Reloading...', 'success');
            setTimeout(function () { window.location.reload(); }, 500);
        }).catch(function (error) {
            showMessage(error.message, 'error');
        });
    });

    document.querySelectorAll('.game-editor').forEach(function (form) {
        var editButton = form.querySelector('.edit-game');
        var cancelButton = form.querySelector('.cancel-game');
        var openStudioButton = form.querySelector('.open-studio-game');

        if (editButton) {
            editButton.addEventListener('click', function () {
                form.dataset.originalName = form.querySelector('.game-name').value;
                form.dataset.originalDescription = form.querySelector('.game-description').value;
                form.dataset.originalPublic = form.querySelector('.game-public').value;
                form.dataset.originalVideo = form.querySelector('.game-video').value;
                form.classList.add('editing');
                editButton.style.display = 'none';
                if (cancelButton) cancelButton.style.display = '';
            });
        }
        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                form.querySelector('.game-name').value = form.dataset.originalName || form.querySelector('.game-name').value;
                form.querySelector('.game-description').value = form.dataset.originalDescription || '';
                form.querySelector('.game-public').value = form.dataset.originalPublic || form.querySelector('.game-public').value;
                form.querySelector('.game-video').value = form.dataset.originalVideo || '';
                form.classList.remove('editing');
                cancelButton.style.display = 'none';
                editButton.style.display = '';
            });
        }
        if (openStudioButton) {
            openStudioButton.addEventListener('click', function () {
                if (typeof openCapsuleLauncher === 'function') {
                    openCapsuleLauncher(form.getAttribute('data-game-id'), true);
                }
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var button = form.querySelector('.save-game');
            var videoUrl = String(form.querySelector('.game-video').value || '').trim();
            var existingMedia = parseExistingMedia(form);
            var removedImageIds = Array.from(form.querySelectorAll('.current-game-image.marked-remove')).map(function (image) {
                return Number(image.getAttribute('data-asset-id'));
            });
            var remainingImageCount = existingMedia.filter(function (item) {
                return String(item.type).toLowerCase() === 'image' && removedImageIds.indexOf(Number(item.id)) === -1;
            }).length;
            var newImageInput = form.querySelector('.game-images');
            if (remainingImageCount + (newImageInput ? newImageInput.files.length : 0) > 5) {
                showMessage('You can have a maximum of 5 images per game.', 'error');
                return;
            }
            var imageUploads = uploadFiles(newImageInput, 'image');
            var dwfInput = form.querySelector('.game-dwf');
            var dwfUpload = dwfInput && dwfInput.files.length ? uploadFile(dwfInput.files[0], 'dwf') : Promise.resolve(null);
            var videoAsset = videoUrl ? uploadVideo(videoUrl) : Promise.resolve(null);

            Promise.all([imageUploads, dwfUpload, videoAsset]).then(function (uploads) {
                var media = withoutType(existingMedia, 'video').filter(function (item) {
                    return String(item.type).toLowerCase() !== 'image' || removedImageIds.indexOf(Number(item.id)) === -1;
                });
                media = media.concat(uploads[0].map(mediaEntry));
                if (uploads[1]) {
                    media = withoutType(media, 'dwf');
                    media.push(mediaEntry(uploads[1]));
                }
                if (uploads[2]) media.push(mediaEntry(uploads[2]));
                var payload = {
                    id: Number(form.getAttribute('data-game-id')),
                    name: form.querySelector('.game-name').value,
                    desc: form.querySelector('.game-description').value,
                    public: Number(form.querySelector('.game-public').value),
                    thumbnail_urls: media
                };
                return sendGame('/api/v1/games/update?id=' + encodeURIComponent(payload.id), payload, button);
            }).then(function () {
                showMessage('Game updated successfully. Reloading...', 'success');
                setTimeout(function () { window.location.reload(); }, 500);
            }).catch(function (error) {
                showMessage(error.message, 'error');
            });
        });
    });
}());
</script>
</body>
</html>
