<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games - Capsule Beta</title>
    <link class="styles" rel="stylesheet" href="/assets/css/Capsule.css">

    <?php include ROOT_PATH . 'includes/meta.php'; ?>
    <?php include ROOT_PATH . 'includes/icon.php'; ?>

    <style>
    /* ---------- Games page (Capsule.css theme'ini kullanır) ---------- */

    .games-page{
        width:970px;
        margin:20px auto;
    }

    .games-top{
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        margin-bottom:10px;
    }

    .games-top h1{
        margin:0;
        font-size:28px;
        font-weight:bold;
        color:var(--text);
    }

    .search input[type="text"]{
        width:180px;
        padding:6px 8px;
        border:1px solid var(--panel-border);
        border-radius:4px;
        font-size:12px;
        background:var(--panel);
        color:var(--text);
    }

    /* Layout */
    .games-content{
        display:flex;
        gap:15px;
        height:644px;
    }

    .games-sidebar{
        width:170px;
        flex-shrink:0;
        background:var(--panel);
        border:1px solid var(--panel-border);
        border-radius:6px;
        padding:12px;
    }

    .games-sidebar h3{
        font-size:13px;
        color:var(--text);
        font-weight:bold;
        margin:12px 0 6px;
    }

    .games-sidebar h3:first-child{
        margin-top:0;
    }

    .games-sidebar a{
        display:block;
        padding:2px 0;
        font-size:12px;
        color:#095fb8;
        text-decoration:none;
    }

    .games-sidebar a:hover{
        text-decoration:underline;
    }

    .games-sidebar a.active{
        color:var(--text);
        font-weight:bold;
    }

    .games-main{
        flex:1;
        background:var(--panel);
        border:1px solid var(--panel-border);
        border-radius:6px;
        padding:15px;
        min-height:200px;

        display:flex;
        flex-direction:column;
    }

    .games-grid{
        display:grid;
        grid-template-columns:repeat(4,170px);
        gap:20px 15px;
        flex:1;
        align-content:start;
    }

    .game-card{
        width:170px;
        display:flex;
        flex-direction:column;
    }

    .game-card img{
        display:block;
        width:170px;
        height:96px;
        object-fit:cover;
        background:#ddd;
        border:1px solid #cfcfcf;
    }

    .game-card .game-title{
        margin-top:6px;
        font-size:12px;
        font-weight:bold;
        line-height:1.3;
    }

    .game-card .game-title a{
        color:#095fb8;
        text-decoration:none;
    }

    .game-card .game-title a:hover{
        text-decoration:underline;
    }

    .game-card .creator{
        font-size:11px;
        color:var(--muted);
        margin-top:2px;
    }

    .game-card .players{
        font-size:11px;
        color:#c0392b;
        font-weight:bold;
        margin-top:2px;
        display:flex;
        align-items:center;
        justify-content:space-between;
    }

    .game-card .players .icons{
        color:var(--muted);
        font-size:12px;
    }

    .games-loading,
    .games-empty,
    .games-error{
        width:100%;
        padding:40px 10px;
        text-align:center;
        color:var(--muted);
        font-size:13px;
        grid-column: span 4;
    }

    .games-error{
        color:#c0392b;
    }

    /* Pagination */
    .games-pagination{
        margin-top:auto;
        padding-top:15px;
        border-top:1px solid #eee;

        display:flex;
        justify-content:center;
        align-items:center;
        gap:10px;

        font-size:12px;
        color:var(--muted);
    }

    .games-pagination a,
    .games-pagination span.btn-disabled{
        display:flex;
        align-items:center;
        justify-content:center;
        width:28px;
        height:28px;
        background:var(--button-primary);
        color:#fff;
        border-radius:4px;
        text-decoration:none;
        cursor:pointer;
        border:none;
        font-size:14px;
    }

    .games-pagination a:hover{
        background:var(--button-primary-hover);
    }

    .games-pagination span.btn-disabled{
        background:#ccc;
        cursor:default;
    }
    </style>

</head>
<body>

<?php include ROOT_PATH . 'includes/header.php'; ?>

<div class="games-page">

    <div class="games-top">
        <h1>Games</h1>

        <div class="search">
            <input type="text" id="gameSearchInput" placeholder="Search Games">
        </div>
    </div>

    <div class="games-content">

        <div class="games-sidebar">

            <h3>Sorted By:</h3>
            <a href="#" class="active">Relevance</a>
            <a href="#">Popular</a>
            <a href="#">Most Favorited</a>
            <a href="#">Featured</a>

            <h3>Genres:</h3>
            <a href="#" class="active">All</a>
            <a href="#">Building</a>
            <a href="#">Horror</a>
            <a href="#">Town and City</a>
            <a href="#">Military</a>
            <a href="#">Comedy</a>
            <a href="#">Medieval</a>
            <a href="#">Adventure</a>
            <a href="#">Sci-Fi</a>
            <a href="#">Naval</a>
            <a href="#">FPS</a>
            <a href="#">RPG</a>
            <a href="#">Sports</a>
            <a href="#">Fighting</a>
            <a href="#">Western</a>

        </div>

        <div class="games-main">
            <div id="gamesGrid" class="games-grid">
                <div class="games-loading">Loading...</div>
            </div>
            <div id="gamesPagination" class="games-pagination"></div>
        </div>

    </div>

</div>

<?php include ROOT_PATH . 'includes/bottom.php'; ?>

<script>
(function () {
    var userCache = {};

    var gridEl = document.getElementById('gamesGrid');
    var pagerEl = document.getElementById('gamesPagination');
    var searchInput = document.getElementById('gameSearchInput');
    var searchTimeout = null;

    function getParamsFromUrl() {
        var params = new URLSearchParams(window.location.search);
        return {
            page: parseInt(params.get('page'), 10) || 1,
            query: params.get('q') || ''
        };
    }

    function getThumbnail(game) {
        var media = Array.isArray(game.thumbnail_urls) ? game.thumbnail_urls : [];
        for (var i = 0; i < media.length; i++) {
            var item = media[i];
            var type = String(item && item.type ? item.type : '').toLowerCase();
            var value = item && (item.url || item.value) ? (item.url || item.value) : '';
            if (type === 'image' && /^https?:\/\//i.test(value)) {
                return value;
            }
        }
        return '/assets/images/capsuleTemplate.png';
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function gameSlug(name) {
        return String(name || 'game')
            .replace(/[ıİ]/g, 'i')
            .replace(/[ğĞ]/g, 'g')
            .replace(/[üÜ]/g, 'u')
            .replace(/[şŞ]/g, 's')
            .replace(/[öÖ]/g, 'o')
            .replace(/[çÇ]/g, 'c')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-zA-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'game';
    }

    function resolveAndRenderCreator(userId, placeholderId) {
        if (!userId) {
            var el = document.getElementById(placeholderId);
            if (el) el.innerHTML = 'by <span style="font-weight: bold;">System</span>';
            return;
        }

        if (userCache[userId]) {
            updateCreatorElement(placeholderId, userId, userCache[userId]);
            return;
        }

        fetch('/api/v1/users/info?id=' + encodeURIComponent(userId))
            .then(function (res) {
                return res.ok ? res.json() : null;
            })
            .then(function (data) {
                if (data && data.status === 'success' && data.user) {
                    userCache[userId] = data.user.username;
                    updateCreatorElement(placeholderId, userId, data.user.username);
                } else {
                    updateCreatorElement(placeholderId, userId, 'Unknown User');
                }
            })
            .catch(function () {
                updateCreatorElement(placeholderId, userId, 'Unknown User');
            });
    }

    function updateCreatorElement(elementId, userId, username) {
        var el = document.getElementById(elementId);
        if (el) {
            if (username === 'Unknown User') {
                el.innerHTML = 'by <span style="color: var(--muted); font-weight: bold;">Unknown User</span>';
            } else {
                el.innerHTML = 'by <a href="/users/' + encodeURIComponent(userId) + '" style="color: #095fb8; text-decoration: none; font-weight: bold;">' + escapeHtml(username) + '</a>';
            }
        }
    }

    function renderGames(games) {
        if (!games || games.length === 0) {
            gridEl.innerHTML = '<div class="games-empty">No games found.</div>';
            return;
        }

        var html = '';
        games.forEach(function (game) {
            var creatorId = game.ownerUserId || game.creator_id || null;
            var placeholderId = 'creator-placeholder-' + game.id;
            var thumbnailId = 'thumbnail-' + game.id;
            
            html += ''
                + '<div class="game-card">'
                + '<img id="' + thumbnailId + '" src="' + escapeHtml(getThumbnail(game)) + '" alt="' + escapeHtml(game.name) + '">'
                + '<div class="game-title"><a href="/games/' + encodeURIComponent(game.id) + '/' + encodeURIComponent(gameSlug(game.name)) + '">' + escapeHtml(game.name) + '</a></div>'
                + '<div class="creator" id="' + placeholderId + '">by <span style="color: var(--muted);">Loading...</span></div>'
                + '<div class="players">'
                + '<span>Max ' + escapeHtml(game.max_players) + ' players</span>'
                + '<span class="icons">🛠 👤</span>'
                + '</div>'
                + '</div>';

            setTimeout(function() {
                if (game.owner && game.owner.username) {
                    userCache[creatorId] = game.owner.username;
                }
                resolveAndRenderCreator(creatorId, placeholderId);

                var media = Array.isArray(game.thumbnail_urls) ? game.thumbnail_urls : [];
                var imageAsset = media.find(function (asset) {
                    return asset && String(asset.type || '').toLowerCase() === 'image' && asset.id;
                });
                if (imageAsset && !/^https?:\/\//i.test(String(imageAsset.value || imageAsset.url || ''))) {
                    fetch('/api/v1/assets?id=' + encodeURIComponent(imageAsset.id) + '&type=image')
                        .then(function (response) { return response.ok ? response.json() : null; })
                        .then(function (data) {
                            var resolved = data && (data.asset || data.data);
                            var image = resolved && resolved.url;
                            var imageEl = document.getElementById(thumbnailId);
                            if (imageEl && image) imageEl.src = image;
                        })
                        .catch(function () {});
                }
            }, 0);
        });

        gridEl.innerHTML = html;
    }

    function renderPagination(pagination, searchQuery) {
        var currentPage = pagination.current_page || 1;
        var totalPages = pagination.total_pages || 1;
        var qParam = searchQuery ? '&q=' + encodeURIComponent(searchQuery) : '';

        var html = '';

        if (currentPage > 1) {
            html += '<a href="?page=' + (currentPage - 1) + qParam + '">&#9664;</a>';
        } else {
            html += '<span class="btn-disabled">&#9664;</span>';
        }

        html += '<span>' + currentPage + ' of ' + Math.max(totalPages, 1) + '</span>';

        if (currentPage < totalPages) {
            html += '<a href="?page=' + (currentPage + 1) + qParam + '">&#9654;</a>';
        } else {
            html += '<span class="btn-disabled">&#9654;</span>';
        }

        pagerEl.innerHTML = html;
    }

    function loadGames(page, query) {
        gridEl.innerHTML = '<div class="games-loading">Loading...</div>';
        pagerEl.innerHTML = '';

        var endpoint = query 
            ? '/api/v1/games/search?q=' + encodeURIComponent(query) + '&page=' + page
            : '/api/v1/games/list?page=' + page;

        fetch(endpoint, {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function (data) {
                if (data.status !== 'success') {
                    throw new Error(data.message || 'Unknown API error.');
                }
                renderGames(data.games);
                renderPagination(data.pagination || {}, query);
            })
            .catch(function (err) {
                gridEl.innerHTML = '<div class="games-error">⚠ API request failed: ' + escapeHtml(err.message) + '</div>';
            });
    }

    // İlk Durum Yüklemesi
    var initialParams = getParamsFromUrl();
    if (initialParams.query) {
        searchInput.value = initialParams.query;
    }
    loadGames(initialParams.page, initialParams.query);

    // Arama Kutusu Event Listener (Debounced)
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            var q = searchInput.value.trim();
            var newUrl = window.location.pathname + '?page=1' + (q ? '&q=' + encodeURIComponent(q) : '');
            window.history.pushState({ path: newUrl }, '', newUrl);
            loadGames(1, q);
        }, 300);
    });
})();
</script>

</body>
</html>
