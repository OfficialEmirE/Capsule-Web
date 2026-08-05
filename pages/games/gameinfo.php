<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$viewerId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

$gameId = $id ?? null;
if (!$gameId || (int) $gameId < 1) {
    http_response_code(404);
    $errorCode = 404;
    require ROOT_PATH . 'error.php';
    exit;
}

$gameOgData = [];
try {
    if (!function_exists('api_db')) require_once ROOT_PATH . 'api/config.php';
    $gameOgStmt = api_db()->prepare('SELECT name, `desc` FROM games WHERE id = ? LIMIT 1');
    $gameOgStmt->execute([(int)$gameId]);
    $gameOgData = $gameOgStmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $gameOgData = [];
}
$gameOgTitle = (string)($gameOgData['name'] ?? 'Capsule Game');
$gameOgDescription = trim((string)($gameOgData['desc'] ?? '')) ?: 'Play this game on Capsule.';
$gameOgUrl = 'https://capsule.rf.gd/games/' . (int)$gameId;
$gameOgImage = '/assets/images/capsuleTemplate.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Info - Capsule Beta</title>
    <?php include ROOT_PATH . 'includes/meta.php'; ?>
    <!-- Game-specific Open Graph metadata -->
    <meta property="og:site_name" content="Capsule">
    <meta property="og:url" content="<?php echo htmlspecialchars($gameOgUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($gameOgTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($gameOgDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://capsule.rf.gd/assets/images/capsuleTemplate.png">
    <meta name="description" content="<?php echo htmlspecialchars($gameOgDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($gameOgTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($gameOgDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="https://capsule.rf.gd/assets/images/capsuleTemplate.png">
    <meta name="theme-color" content="#FFFFFF">
    <link rel="icon" type="image/x-icon" href="/assets/images/favicons/favicon.ico">
    <link rel="stylesheet" href="/assets/css/Capsule.css">
    <style>
        .game-page { width:970px; margin:22px auto 30px; }
        .game-shell { background:var(--panel); border:1px solid var(--panel-border); border-radius:7px; overflow:hidden; box-shadow:0 1px 2px rgba(0,0,0,.04); }
        .game-owner { color:var(--muted); font-size:12px; }
        .game-owner a { color:var(--button-primary); text-decoration:none; font-weight:bold; }
        .game-owner a:hover { text-decoration:underline; }
        .owner-admin-badge { display:inline-block; margin-left:5px; padding:2px 5px; border-radius:3px; background:#337ab7; color:#fff; font-size:10px; font-weight:bold; vertical-align:middle; }
        .game-body { padding:20px; }
        .game-toolbar { display:flex; justify-content:flex-end; align-items:center; gap:15px; margin-bottom:18px; }
        .game-actions { display:flex; align-items:center; gap:9px; }
        .play-button { display:inline-block; background:#4a9b4a; border:1px solid #3d843d; color:#fff; padding:9px 24px; border-radius:3px; font-size:13px; font-weight:bold; }
        .play-button:hover { background:#398539; }
        .play-button-disabled { display:inline-block; background:#d7d7d7; border:1px solid #c4c4c4; color:#888; padding:9px 22px; border-radius:3px; font-size:13px; font-weight:bold; cursor:not-allowed; }
        .media-row { display:grid; grid-template-columns:minmax(0,1fr) 245px; gap:20px; align-items:start; }
        .media-side { display:flex; flex-direction:column; gap:15px; }
        .media-actions { border:1px solid var(--panel-border); border-radius:5px; padding:12px; background:#f8f8f8; display:flex; align-items:center; justify-content:center; }
        .media-actions .play-button { width:100%; text-align:center; }
        .media-actions .play-button-disabled { width:100%; box-sizing:border-box; text-align:center; }
        .game-layout { display:flex; flex-direction:column; gap:15px; }
        .game-section { border:1px solid var(--panel-border); border-radius:5px; padding:15px; }
        .game-section h1, .game-section h2, .details h2 { font-size:18px; margin:0 0 10px; padding-bottom:8px; border-bottom:1px solid var(--panel-border); color:var(--text); }
        .details h2 { display:flex; justify-content:space-between; align-items:center; }
        .game-title-row { display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .game-title-row h1 { flex:1; }
        .report-game-button { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; flex:0 0 auto; border:1px solid #c0392b; background:#e74c3c; color:#fff; border-radius:3px; cursor:pointer; }
        .report-game-button:hover { background:#c0392b; }
        .report-game-button svg { width:16px; height:16px; stroke:#fff; }
        .media-stage { position:relative; background:#15191d; border:1px solid #292d31; border-radius:4px; overflow:hidden; }
        .media-image-frame { width:100%; aspect-ratio:16 / 9; background:#15191d; overflow:hidden; }
        .media-stage-image { display:block; width:100%; height:100%; object-fit:cover; background:#15191d; }
        .media-arrow { position:absolute; z-index:2; top:50%; transform:translateY(-50%); width:34px; height:54px; border:0; background:rgba(0,0,0,.55); color:#fff; font-size:28px; line-height:1; cursor:pointer; opacity:.85; }
        .media-arrow:hover { background:rgba(0,0,0,.8); opacity:1; }
        .media-arrow.left { left:0; border-radius:0 3px 3px 0; }
        .media-arrow.right { right:0; border-radius:3px 0 0 3px; }
        .media-empty { padding:70px 10px; text-align:center; color:#999; font-size:12px; }
        .media-video-frame { width:100%; aspect-ratio:16 / 9; background:#000; overflow:hidden; }
        .media-video-frame iframe, .media-video-frame video { display:block; width:100%; height:100%; border:0; background:#000; }
        .video-label { padding:7px 10px; color:#ddd; font-size:12px; }
        .game-description { min-height:90px; color:#444; font-size:13px; line-height:1.6; white-space:pre-wrap; }
        .details { border:1px solid var(--panel-border); border-radius:5px; padding:15px; height:max-content; }
        .detail-row { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f0f0f0; color:var(--muted); font-size:12px; }
        .detail-row:last-child { border-bottom:0; }
        .detail-row strong { color:var(--text); text-align:right; }
        .status-public { color:#348b48 !important; }
        .game-loading,.game-error { padding:90px 10px; text-align:center; color:var(--muted); font-size:13px; }
        .game-error { color:#c0392b; }
        .media-empty { padding:12px 10px; }
        @media (max-width:990px) { .navbar,.footer{min-width:0;} .navbar-inner,.footer-inner,.game-page{width:calc(100% - 24px);} }
        @media (max-width:650px) { .game-page{margin-top:12px;} .game-body{padding:14px;} .game-toolbar{align-items:flex-start; flex-direction:column-reverse;} .media-row{grid-template-columns:1fr;} .media-actions{padding:10px;} .game-actions{width:100%; justify-content:space-between;} .play-button{flex:1; text-align:center;} }
    </style>
</head>
<body>
<?php include ROOT_PATH . 'includes/header.php'; ?>

<main class="game-page">
    <section id="gameShell" class="game-shell">
        <div class="game-loading">Loading game...</div>
    </section>
</main>

<?php include ROOT_PATH . 'includes/bottom.php'; ?>
<script>
(function () {
    var gameId = <?php echo json_encode((int) $gameId); ?>;
    var viewerId = <?php echo json_encode($viewerId ? (int)$viewerId : 0); ?>;
    var shell = document.getElementById('gameShell');
    var fallback = '/assets/images/capsuleTemplate.png';

    function escapeHtml(value) {
        var node = document.createElement('div');
        node.textContent = value == null ? '' : String(value);
        return node.innerHTML;
    }

    function imageValue(item) {
        if (typeof item === 'string') return item;
        return item && (item.value || item.url) ? (item.value || item.url) : '';
    }

    function render(game) {
        var media = Array.isArray(game.thumbnail_urls) ? game.thumbnail_urls : [];
        var images = media.filter(function (item) {
            var type = String((item && item.type) || '').toLowerCase();
            var value = imageValue(item).toLowerCase();
            return !type.includes('video') && !type.includes('dwf') && !/\.(mp4|webm|ogg)(\?|$)/.test(value) && !/youtube\.com|youtu\.be|vimeo\.com/.test(value);
        }).map(imageValue).filter(Boolean);
        var videos = media.filter(function (item) {
            var type = String((item && item.type) || '').toLowerCase();
            var value = imageValue(item).toLowerCase();
            return (type.includes('video') && value !== '') || (!type.includes('dwf') && (/\.(mp4|webm|ogg)(\?|$)/.test(value) || /youtube\.com|youtu\.be|vimeo\.com/.test(value)));
        });
        var mediaItems = images.map(function (src) { return { type: 'image', src: src }; })
            .concat(videos.map(function (item) { return { type: 'video', item: item }; }));
        if (!mediaItems.length) mediaItems.push({ type: 'image', src: fallback });
        var ownerId = game.ownerUserId || game.creator_id || '';
        var ownerName = game.owner && game.owner.username ? game.owner.username : 'Unknown User';
        var isPublic = String(game.public) === '1';
        var hasDwf = Number(game.dwf_id) > 0;
        var canReport = viewerId > 0 && ownerId && String(viewerId) !== String(ownerId);
        var createdDate = formatDate(game.created_at || game.createdAt);
        var updatedDate = formatDate(game.updated_at || game.updatedAt);

        var arrows = mediaItems.length > 1
            ? '<button id="mediaPrev" class="media-arrow left" type="button" aria-label="Previous media">&#10094;</button>'
            + '<button id="mediaNext" class="media-arrow right" type="button" aria-label="Next media">&#10095;</button>'
            : '';

        function mediaMarkup(item) {
            if (!item || item.type === 'image') {
                var imageSrc = item && item.src ? item.src : fallback;
                return '<div class="media-image-frame"><img id="gameMediaImage" class="media-stage-image" src="' + escapeHtml(imageSrc) + '" alt="' + escapeHtml(game.name || 'Game') + '"></div>';
            }

            var source = imageValue(item.item);
            var safeSource = escapeHtml(source);
            var lowerSource = source.toLowerCase();
            var player = /youtube\.com|youtu\.be|vimeo\.com/.test(lowerSource)
                ? '<iframe src="' + escapeHtml(toEmbedUrl(source)) + '" title="Game video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                : '<video controls preload="metadata" src="' + safeSource + '"></video>';
            return '<div class="media-video-frame">' + player + '</div>';
        }

        var playControl = isPublic && hasDwf
            ? '<a class="play-button" href="/download?game_id=' + encodeURIComponent(game.id) + '">Play</a>'
            : '<span class="play-button-disabled" title="This game cannot be played without a DWF file">Unavailable</span>';

        shell.innerHTML = '<div class="game-body"><div class="game-layout"><div class="media-row"><section class="game-section"><div class="game-title-row"><h1>' + escapeHtml(game.name || 'Untitled Game') + '</h1>'
            + '</div><div class="media-stage">' + arrows + '<div id="mediaContent">' + mediaMarkup(mediaItems[0]) + '</div></div></section><div class="media-side">'
            + '<aside class="details"><h2>Game Details' + (canReport ? '<button id="reportGameButton" class="report-game-button" type="button" title="Report Game" aria-label="Report Game"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 22V4"></path><path d="M4 4h14l-2 4 2 4H4"></path></svg></button>' : '') + '</h2>'
            + '<div class="detail-row"><span>Created by</span><strong id="detailsOwner">' + escapeHtml(ownerName) + '</strong></div>'
            + '<div class="detail-row"><span>Players</span><strong>Max ' + escapeHtml(game.max_players || '0') + '</strong></div>'
            + '<div class="detail-row"><span>Visibility</span><strong class="' + (isPublic ? 'status-public' : '') + '">' + (isPublic ? 'Public' : 'Private') + '</strong></div>'
            + '<div class="detail-row"><span>Created</span><strong>' + escapeHtml(createdDate) + '</strong></div>'
            + '<div class="detail-row"><span>Updated</span><strong>' + escapeHtml(updatedDate) + '</strong></div></aside>'
            + '<div class="media-actions">' + playControl + '</div></div></div>'
            + '<section class="game-section"><h2>About this game</h2><div class="game-description">' + escapeHtml(game.desc || 'The creator has not added a description yet.') + '</div></section></div></div>';

        var currentMediaIndex = 0;
        var mediaContent = document.getElementById('mediaContent');

        function showMedia(index) {
            if (!mediaItems.length || !mediaContent) return;
            currentMediaIndex = (index + mediaItems.length) % mediaItems.length;
            mediaContent.innerHTML = mediaMarkup(mediaItems[currentMediaIndex]);
        }

        var previousButton = document.getElementById('mediaPrev');
        var nextButton = document.getElementById('mediaNext');
        if (previousButton) previousButton.addEventListener('click', function () { showMedia(currentMediaIndex - 1); });
        if (nextButton) nextButton.addEventListener('click', function () { showMedia(currentMediaIndex + 1); });
        var reportButton = document.getElementById('reportGameButton');
        if (reportButton) {
            reportButton.addEventListener('click', function () {
                window.location.href = '/report?game_id=' + encodeURIComponent(game.id) + '&game_name=' + encodeURIComponent(game.name || '');
            });
        }

        if (ownerId) {
            fetch('/api/v1/users/info?id=' + encodeURIComponent(ownerId))
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (data) {
                    if (data && data.status === 'success' && data.user) {
                        var adminBadge = Number(data.user.is_admin) === 1 ? '<span class="owner-admin-badge">Admin</span>' : '';
                        var ownerLink = '<a href="/users/' + encodeURIComponent(ownerId) + '">' + escapeHtml(data.user.username) + '</a>' + adminBadge;
                        document.getElementById('detailsOwner').innerHTML = ownerLink;
                    }
                });
        }
        document.title = (game.name || 'Game Info') + ' - Capsule Beta';
    }

    function formatDate(value) {
        if (!value) return 'Unknown';
        var date = new Date(value);
        if (isNaN(date.getTime())) return 'Unknown';
        return date.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
    }

    function toEmbedUrl(url) {
        try {
            var parsed = new URL(url);
            if (parsed.hostname.includes('youtu.be')) return 'https://www.youtube.com/embed/' + parsed.pathname.replace('/', '');
            if (parsed.hostname.includes('youtube.com')) return 'https://www.youtube.com/embed/' + (parsed.searchParams.get('v') || parsed.pathname.split('/').pop());
            if (parsed.hostname.includes('vimeo.com')) return 'https://player.vimeo.com/video/' + parsed.pathname.split('/').pop();
        } catch (error) {}
        return url;
    }

    function resolveAssets(game) {
        var assets = Array.isArray(game.thumbnail_urls) ? game.thumbnail_urls : [];
        if (!assets.length) return Promise.resolve(game);

        return Promise.all(assets.map(function (asset) {
            if (!asset || !asset.type || !asset.id) return Promise.resolve(asset);

            return fetch('/api/v1/assets?id=' + encodeURIComponent(asset.id) + '&type=' + encodeURIComponent(asset.type), {
                headers: { Accept: 'application/json' }
            }).then(function (response) {
                if (!response.ok) {
                    var statusError = new Error('Asset service unavailable.');
                    statusError.httpStatus = response.status;
                    throw statusError;
                }
                return response.json();
            }).then(function (data) {
                var resolved = data.asset || data.data;
                if (data.status !== 'success' || !resolved || !resolved.url) throw new Error(data.message || 'Asset resolution failed.');
                return { type: resolved.type || asset.type, value: resolved.url, id: asset.id };
            }).catch(function (error) {
                if (error && (error.httpStatus === 403 || error.httpStatus === 404)) throw error;
                return asset;
            });
        })).then(function (resolvedAssets) {
            game.thumbnail_urls = resolvedAssets;
            return game;
        });
    }

    fetch('/api/v1/games/info?id=' + encodeURIComponent(gameId), { headers: { Accept: 'application/json' } })
        .then(function (response) {
            if (!response.ok) {
                var statusError = new Error('Game request failed.');
                statusError.httpStatus = response.status;
                throw statusError;
            }
            return response.json();
        })
        .then(function (data) { if (data.status !== 'success' || !data.game) throw new Error(data.message || 'Game not found.'); return resolveAssets(data.game); })
        .then(function (game) { render(game); })
        .catch(function (error) {
            var status = error && (error.httpStatus === 403 || error.httpStatus === 404) ? error.httpStatus : 0;
            if (status) {
                window.location.replace('/error.php?code=' + status);
                return;
            }
            shell.innerHTML = '<div class="game-error">' + escapeHtml(error.message) + '</div>';
        });
}());
</script>
</body>
</html>
