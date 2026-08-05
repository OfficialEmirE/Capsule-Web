<?php
ob_start();

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Users - Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/meta.php'; ?>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">

        <style>
            .members-container {
                width: 970px;
                margin: 20px auto;
                background: var(--panel);
                border: 1px solid var(--panel-border);
                border-radius: 6px;
                padding: 20px;
            }

            .members-header {
                font-size: 18px;
                font-weight: bold;
                border-bottom: 1px solid var(--panel-border);
                padding-bottom: 10px;
                margin-bottom: 20px;
                color: var(--text);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .search-box-wrapper {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .search-input {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                color: var(--text);
                padding: 6px 12px;
                border-radius: 4px;
                font-size: 13px;
                outline: none;
                width: 200px;
                transition: border-color 0.2s;
            }

            .search-input:focus {
                border-color: #3498db;
            }

            .search-reset {
                font-size: 12px;
                color: #3498db;
                text-decoration: none;
                cursor: pointer;
            }

            .search-reset:hover {
                text-decoration: underline;
            }

            .total-members-badge {
                font-size: 12px;
                background: var(--panel-border);
                padding: 4px 8px;
                border-radius: 12px;
                color: var(--muted);
                font-weight: normal;
                margin-left: 10px;
            }

            .header-left-side {
                display: flex;
                align-items: center;
            }

            .members-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
            }

            .member-card {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                border-radius: 6px;
                padding: 15px;
                text-align: center;
                text-decoration: none;
                color: var(--text);
                transition: transform 0.2s, border-color 0.2s;
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .member-card:hover {
                transform: translateY(-2px);
                border-color: #3498db;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            }

            .member-avatar-container {
                width: 80px;
                height: 80px;
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .member-avatar-container svg {
                width: 100%;
                height: 100%;
                display: block;
            }

            .member-username {
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 4px;
                word-break: break-all;
                color: var(--text);
            }

            .member-id {
                font-size: 11px;
                color: var(--muted);
            }

            .member-admin-badge {
                display: inline-block;
                margin-left: 5px;
                padding: 2px 6px;
                border-radius: 3px;
                background: #337ab7;
                color: #fff;
                font-size: 10px;
                font-weight: bold;
                vertical-align: middle;
            }

            .pagination-container {
                display: flex;
                justify-content: center;
                gap: 8px;
                margin-top: 30px;
                border-top: 1px solid var(--panel-border);
                padding-top: 20px;
            }

            .page-btn {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                color: var(--text);
                padding: 6px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                text-decoration: none;
                transition: all 0.2s;
            }

            .page-btn:hover:not(.disabled) {
                background: #3498db;
                color: #fff;
                border-color: #3498db;
            }

            .page-btn.active {
                background: #3498db;
                color: #fff;
                border-color: #3498db;
                font-weight: bold;
                pointer-events: none;
            }

            .page-btn.disabled {
                opacity: 0.5;
                cursor: not-allowed;
                pointer-events: none;
            }

            .loading-text {
                grid-column: span 4;
                text-align: center;
                padding: 40px;
                color: var(--muted);
                font-style: italic;
            }
        </style>
    </head>
    <body>
        <?php include ROOT_PATH . 'includes/header.php'; ?>

        <div class="members-container">
            
            <div class="members-header">
                <div class="header-left-side">
                    <h2>Users</h2>
                    <span id="totalMembers" class="total-members-badge">Loading...</span>
                </div>
                <div class="search-box-wrapper">
                    <a id="searchReset" class="search-reset" style="display: none;">[Reset]</a>
                    <input type="text" id="searchQuery" class="search-input" placeholder="Search username...">
                </div>
            </div>

            <div id="membersGrid" class="members-grid">
                <div class="loading-text">Loading members...</div>
            </div>

            <div id="pagination" class="pagination-container"></div>

        </div>

        <?php include ROOT_PATH . 'includes/bottom.php'; ?>

        <script>
        (function() {
            var urlParams = new URLSearchParams(window.location.search);
            var currentPage = parseInt(urlParams.get('page')) || 1;
            var currentSearch = urlParams.get('q') || '';
            
            var membersGrid = document.getElementById('membersGrid');
            var pagination = document.getElementById('pagination');
            var totalMembersBadge = document.getElementById('totalMembers');
            var searchQueryInput = document.getElementById('searchQuery');
            var searchReset = document.getElementById('searchReset');
            
            var bodySvgTemplate = "";
            var searchTimeout = null;

            if (currentSearch) {
                searchQueryInput.value = currentSearch;
                searchReset.style.display = 'inline';
            }

            fetch('/assets/images/body.svg')
                .then(function(res) {
                    return res.text();
                })
                .then(function(svgText) {
                    bodySvgTemplate = svgText;
                    loadMembers(currentPage, currentSearch);
                })
                .catch(function(err) {
                    console.error("SVG template could not be loaded:", err);
                    loadMembers(currentPage, currentSearch);
                });

            function loadMembers(page, search) {
                var url = search 
                    ? '/api/v1/users/search?q=' + encodeURIComponent(search) + '&page=' + page
                    : '/api/v1/users/list?page=' + page;

                membersGrid.innerHTML = '<div class="loading-text">Loading members...</div>';

                fetch(url)
                    .then(function(res) {
                        if (!res.ok) throw new Error("Could not fetch user list.");
                        return res.json();
                    })
                    .then(function(data) {
                        if (data && data.status === 'success') {
                            renderUsers(data.users);
                            renderPagination(data.pagination, search);
                            totalMembersBadge.textContent = (data.pagination.total_users || 0) + " Total Users";
                        } else {
                            showError("An error occurred while loading users.");
                        }
                    })
                    .catch(function(err) {
                        console.error(err);
                        showError("Failed to fetch data from server.");
                    });
            }

            function renderUsers(users) {
                membersGrid.innerHTML = '';

                if (!users || users.length === 0) {
                    membersGrid.innerHTML = '<div class="loading-text">No registered users found.</div>';
                    return;
                }

                users.forEach(function(user) {
                    var card = document.createElement('a');
                    card.href = '/users/' + user.id;
                    card.className = 'member-card';

                    var avatarContainer = document.createElement('div');
                    avatarContainer.className = 'member-avatar-container';

                    if (bodySvgTemplate) {
                        var parser = new DOMParser();
                        var svgDoc = parser.parseFromString(bodySvgTemplate, "image/svg+xml");
                        var svgElement = svgDoc.querySelector('svg');

                        if (svgElement) {
                            var targetColor = user.avatar ? user.avatar : '#ffffff';
                            var paths = svgElement.querySelectorAll('path');
                            paths.forEach(function(path) {
                                var fill = (path.getAttribute('fill') || '').toLowerCase();
                                var stroke = (path.getAttribute('stroke') || '').toLowerCase();

                                if (
                                    fill === '#ffffff' || fill === '#fff' ||
                                    stroke === '#ffffff' || stroke === '#fff'
                                ) {
                                    path.setAttribute('fill', targetColor);
                                    path.setAttribute('stroke', targetColor);
                                }
                            });
                            avatarContainer.appendChild(svgElement);
                        }
                    } else {
                        avatarContainer.innerHTML = '<div style="width:100%; height:100%; background:#eee; border-radius:50%;"></div>';
                    }

                    var usernameEl = document.createElement('div');
                    usernameEl.className = 'member-username';
                    usernameEl.textContent = user.username;

                    if (Number(user.is_admin) === 1) {
                        var adminBadge = document.createElement('span');
                        adminBadge.className = 'member-admin-badge';
                        adminBadge.textContent = 'Admin';
                        usernameEl.appendChild(adminBadge);
                    }

                    card.appendChild(avatarContainer);
                    card.appendChild(usernameEl);

                    membersGrid.appendChild(card);
                });
            }

            function renderPagination(meta, search) {
                pagination.innerHTML = '';
                var totalPages = meta.total_pages || 1;
                var current = meta.current_page || 1;
                var searchParam = search ? '&q=' + encodeURIComponent(search) : '';

                if (totalPages <= 1) return;

                var prevBtn = document.createElement('a');
                prevBtn.className = 'page-btn' + (current === 1 ? ' disabled' : '');
                prevBtn.textContent = '« Prev';
                if (current > 1) prevBtn.href = '?page=' + (current - 1) + searchParam;
                pagination.appendChild(prevBtn);

                for (var i = 1; i <= totalPages; i++) {
                    var pageBtn = document.createElement('a');
                    pageBtn.className = 'page-btn' + (i === current ? ' active' : '');
                    pageBtn.textContent = i;
                    pageBtn.href = '?page=' + i + searchParam;
                    pagination.appendChild(pageBtn);
                }

                var nextBtn = document.createElement('a');
                nextBtn.className = 'page-btn' + (current === totalPages ? ' disabled' : '');
                nextBtn.textContent = 'Next »';
                if (current < totalPages) nextBtn.href = '?page=' + (current + 1) + searchParam;
                pagination.appendChild(nextBtn);
            }

            function triggerSearch() {
                var query = searchQueryInput.value.trim();
                
                if (query) {
                    searchReset.style.display = 'inline';
                } else {
                    searchReset.style.display = 'none';
                }

                var newUrl = window.location.pathname + '?page=1' + (query ? '&q=' + encodeURIComponent(query) : '');
                window.history.pushState({ path: newUrl }, '', newUrl);
                
                loadMembers(1, query);
            }

            searchQueryInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    triggerSearch();
                }, 300);
            });

            searchReset.addEventListener('click', function() {
                searchQueryInput.value = '';
                searchReset.style.display = 'none';
                triggerSearch();
            });

            function showError(msg) {
                membersGrid.innerHTML = '<div class="loading-text" style="color: #e74c3c;">' + escapeHtml(msg) + '</div>';
            }

            function escapeHtml(str) {
                if (str == null) return '';
                var div = document.createElement('div');
                div.textContent = String(str);
                return div.innerHTML;
            }
        })();
        </script>
    </body>
</html>
