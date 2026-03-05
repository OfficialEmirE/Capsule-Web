<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capsule</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --line: #dedede;
            --card: #ffffff;
            --card-border: #e5e5e5;
            --text: #202020;
            --muted: #5f5f5f;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Nunito", "Segoe UI", sans-serif;
            color: var(--text);
            background: #ffffff;
        }

        .layout {
            min-height: 100vh;
            height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 12px clamp(10px, 2.4vw, 24px);
            border-bottom: 1px solid var(--line);
            background: #ffffff;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .top-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            flex: 1;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            height: clamp(36px, 4.1vw, 52px);
            flex-shrink: 0;
        }

        .brand img {
            height: 100%;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.35));
        }

        .search-box {
            position: relative;
            width: min(520px, 100%);
        }

        .search-box input {
            width: 100%;
            height: 38px;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 0 14px 0 38px;
            font-size: 14px;
            color: var(--text);
            background: #f8f8f8;
            outline: none;
            cursor: default;
        }

        .search-box svg {
            position: absolute;
            width: 16px;
            height: 16px;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
        }

        .sidebar-toggle {
            border: 1px solid var(--line);
            background: #fff;
            color: var(--text);
            border-radius: 10px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .auth-links {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }

        .auth-link {
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            background: #fff;
        }

        .auth-link.primary {
            background: #111;
            color: #fff;
            border-color: #111;
        }

        .account-menu { position: relative; }

        .account-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--line);
            background: #ffffff;
            border-radius: 999px;
            padding: 4px 10px 4px 4px;
            cursor: pointer;
        }

        .account-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--line);
        }

        .account-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            max-width: 140px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .account-arrow {
            color: var(--muted);
            font-size: 12px;
            line-height: 1;
        }

        .dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            min-width: 190px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 6px;
            display: none;
            z-index: 60;
        }

        .dropdown.show { display: block; }

        .dropdown a,
        .dropdown button {
            width: 100%;
            display: block;
            text-align: left;
            border: 0;
            background: transparent;
            text-decoration: none;
            color: var(--text);
            font-size: 14px;
            font-weight: 700;
            border-radius: 8px;
            padding: 9px 10px;
            cursor: pointer;
        }

        .dropdown a:hover,
        .dropdown button:hover { background: #f3f3f3; }

        .dropdown .logout-btn { color: #dc2626; }
        .dropdown .logout-btn:hover { background: #fef2f2; }

        .body {
            display: grid;
            grid-template-columns: clamp(190px, 20vw, 244px) minmax(0, 1fr);
            min-height: 0;
            overflow: hidden;
        }

        .body.sidebar-minimal {
            grid-template-columns: 0 minmax(0, 1fr);
        }

        .body.sidebar-minimal .sidebar {
            display: none;
        }

        .sidebar {
            border-right: 1px solid var(--line);
            padding: clamp(12px, 1.7vw, 20px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 26px;
            background: #fafafa;
            height: 100%;
            min-height: 0;
        }

        .nav-group {
            display: grid;
            gap: 6px;
        }

        .nav-group.bottom {
            border-top: 1px solid var(--line);
            padding-top: 12px;
        }

        .sidebar a {
            color: var(--muted);
            text-decoration: none;
            font-size: clamp(15px, 1.6vw, 20px);
            font-weight: 700;
            padding: 8px 10px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            color: var(--text);
            background: #f1f1f1;
            transform: translateX(3px);
        }

        .content {
            padding: clamp(14px, 2.2vw, 24px);
            overflow-y: auto;
            min-height: 0;
        }

        .page-title {
            margin: 0;
            font-size: clamp(26px, 3.5vw, 44px);
            font-weight: 800;
        }

        .section-head {
            margin: 14px 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-head h2 {
            margin: 0;
            font-size: clamp(18px, 2.2vw, 30px);
            font-weight: 800;
        }

        .section-head::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, #d7d7d7, transparent);
        }

        .status {
            margin: 10px 0;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #f8f8f8;
            color: var(--muted);
            font-weight: 700;
            font-size: 14px;
        }

        .status.error {
            border-color: rgba(255, 120, 120, 0.5);
            color: #7f1d1d;
            background: #fff1f1;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: clamp(10px, 1.5vw, 16px);
        }

        .game-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            overflow: hidden;
            text-decoration: none;
            color: var(--text);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .game-card:hover {
            transform: translateY(-3px);
            border-color: #d2d2d2;
        }

        .game-image-wrap {
            position: relative;
            aspect-ratio: 16 / 9;
            background: #f5f5f5;
            border-bottom: 1px solid var(--line);
        }

        .game-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .game-image-placeholder {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .game-footer {
            padding: 10px 12px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .game-name {
            margin: 0;
            font-size: clamp(14px, 1.3vw, 18px);
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .open-btn {
            min-width: 34px;
            height: 34px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: #ffffff;
            color: var(--text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
        }

        .skeleton {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--line);
            background: #f8f8f8;
        }

        .skeleton-media {
            aspect-ratio: 16 / 9;
            background: linear-gradient(90deg, #f1f1f1, #ebebeb, #f1f1f1);
            background-size: 240% 100%;
            animation: shimmer 1.25s linear infinite;
        }

        .skeleton-footer {
            height: 50px;
            background: linear-gradient(90deg, #f1f1f1, #ebebeb, #f1f1f1);
            background-size: 240% 100%;
            animation: shimmer 1.25s linear infinite;
        }

        @keyframes shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        .hidden { display: none !important; }

        @media (max-width: 1040px) {
            .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 840px) {
            .body { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 1px solid var(--line); }
            .nav-group { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .nav-group.bottom { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 640px) {
            .cards { grid-template-columns: 1fr; }
            .nav-group,
            .nav-group.bottom { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .top-right,
            .top-left { width: 100%; }
            .search-box { flex: 1; }
        }
    </style>
</head>
<body>
    <main class="layout">
        <header class="topbar">
            <div class="top-left">
                <a class="brand" href="index.php" aria-label="Capsule ana sayfa">
                    <img src="http://capsule.net.tr/CapsuleLogo.png" alt="Capsule Logo" id="c-icon">
                </a>
                <label class="search-box" aria-label="Arama kutusu">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" stroke-width="2"></line>
                    </svg>
                    <input type="text" placeholder="Ara..." disabled>
                </label>
                <button id="sidebar-toggle" class="sidebar-toggle" type="button">Menü</button>
            </div>

            <div class="top-right">
                <div class="auth-links hidden" id="auth-links">
                    <a class="auth-link" href="login/login.php">Giriş Yap</a>
                    <a class="auth-link primary" href="login/register.php">Kayıt Ol</a>
                </div>
                <div class="account-menu" id="account-menu-root">
                    <button class="account-button" id="account-button" type="button" aria-haspopup="true" aria-expanded="false">
                        <img class="account-avatar" id="user-avatar" src="https://ui-avatars.com/api/?name=User&background=f3f3f3&color=111111" alt="Kullanıcı avatarı">
                        <span class="account-name" id="username-chip">username</span>
                        <span class="account-arrow" aria-hidden="true">▼</span>
                    </button>
                    <div class="dropdown" id="account-dropdown">
                        <a href="account/">Hesap Ayarları</a>
                        <button id="logout-button" class="logout-btn" type="button">Oturumdan Çık</button>
                    </div>
                </div>
            </div>
        </header>

        <section class="body" id="body-layout">
            <aside class="sidebar" aria-label="Yan menü">
                <nav class="nav-group">
                    <a href="index.php" class="active">Ana Sayfa</a>
                    <a href="games/">Popüler</a>
                    <a href="profile/avatar.php" id="avatar-nav-link">Avatar</a>
                    <a href="studio/">Pazar</a>
                </nav>

                <nav class="nav-group bottom">
                    <a href="profile?username" id="profileUrl">Profil</a>
                    <a href="studio/">Oluştur</a>
                    <a href="https://capsule.instatus.com/" target="_blank" rel="noopener">Status</a>
                    <a href="https://discord.gg/J4arkFaBnf" target="_blank" rel="noopener">Destek</a>
                </nav>
            </aside>

            <section class="content">
                <h1 class="page-title">Ana Sayfa</h1>

                <div id="status" class="status">Oyunlar yükleniyor...</div>

                <div class="section-head">
                    <h2>Önerilenler</h2>
                </div>
                <div id="recommended-grid" class="cards"></div>
            </section>
        </section>
    </main>

    <script>
        const API_URL = "http://capsule.net.tr/api/v1/games/";

        const recommendedGrid = document.getElementById("recommended-grid");
        const statusBox = document.getElementById("status");
        const usernameChip = document.getElementById("username-chip");
        const profileUrl = document.getElementById("profileUrl");
        const authLinks = document.getElementById("auth-links");
        const userAvatar = document.getElementById("user-avatar");
        const accountMenuRoot = document.getElementById("account-menu-root");
        const accountButton = document.getElementById("account-button");
        const accountDropdown = document.getElementById("account-dropdown");
        const logoutButton = document.getElementById("logout-button");
        const avatarNavLink = document.getElementById("avatar-nav-link");
        const sidebarToggle = document.getElementById("sidebar-toggle");
        const bodyLayout = document.getElementById("body-layout");

        function getCookie(name) {
            const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
            const match = document.cookie.match(new RegExp("(?:^|; )" + escaped + "=([^;]*)"));
            return match ? decodeURIComponent(match[1]) : "";
        }

        function createSkeletonCards(container, count) {
            container.innerHTML = Array.from({ length: count }).map(() => `
                <article class="skeleton" aria-hidden="true">
                    <div class="skeleton-media"></div>
                    <div class="skeleton-footer"></div>
                </article>
            `).join("");
        }

        function createGameCard(game) {
            const gameName = game && game.title ? game.title : "Oyunun Adı";
            const gameImage = game && game.image_url ? game.image_url : "";
            const gameId = game && game.id ? String(game.id) : "";
            const gameHref = gameId ? `games/${encodeURIComponent(gameId)}` : "#";
            const safeName = gameName.replace(/</g, "&lt;").replace(/>/g, "&gt;");
            const safeImage = gameImage.replace(/"/g, "&quot;");

            return `
                <a class="game-card" href="${gameHref}">
                    <div class="game-image-wrap">
                        ${gameImage ? `<img class="game-image" src="${safeImage}" alt="${safeName}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">` : ""}
                        <div class="game-image-placeholder" style="display:${gameImage ? "none" : "grid"};">Oyunun resmi</div>
                    </div>
                    <div class="game-footer">
                        <p class="game-name" title="${safeName}">${safeName}</p>
                        <span class="open-btn">></span>
                    </div>
                </a>
            `;
        }

        async function fetchGames() {
            const response = await fetch(API_URL, {
                headers: { "Accept": "application/json" },
                cache: "no-store"
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            if (!payload || payload.status !== "success" || !Array.isArray(payload.data)) {
                throw new Error((payload && payload.message) ? payload.message : "Geçersiz API cevabı");
            }

            return payload.data;
        }

        async function fetchAvatar(username) {
            if (!username) return "";
            const response = await fetch(`api/v1/avatar/?type=get&username=${encodeURIComponent(username)}`, {
                headers: { "Accept": "application/json" },
                cache: "no-store"
            });
            if (!response.ok) return "";
            const payload = await response.json();
            if (!payload || payload.status !== "success") return "";
            return payload.avatar || "";
        }

        function resolveAvatarUrl(username, rawAvatar) {
            const fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(username || "User")}&background=f3f3f3&color=111111`;
            if (!rawAvatar) return fallback;
            if (rawAvatar.startsWith("http://") || rawAvatar.startsWith("https://") || rawAvatar.startsWith("data:image/")) {
                return rawAvatar;
            }
            try {
                const parsed = JSON.parse(rawAvatar);
                if (parsed.faceURL && typeof parsed.faceURL === "string") return parsed.faceURL;
                if (Array.isArray(parsed.avatars) && parsed.avatars.length > 0) {
                    const first = parsed.avatars[0];
                    if (typeof first === "string" && first) return first;
                }
                if (parsed.color && typeof parsed.color === "string") {
                    return `https://ui-avatars.com/api/?name=${encodeURIComponent(username || "User")}&background=${encodeURIComponent(parsed.color.replace("#", ""))}&color=ffffff`;
                }
            } catch (_e) {
                return fallback;
            }
            return fallback;
        }

        function closeAccountMenu() {
            accountDropdown.classList.remove("show");
            accountButton.setAttribute("aria-expanded", "false");
        }

        function setupAccountMenu() {
            accountButton.addEventListener("click", (event) => {
                event.stopPropagation();
                const isOpen = accountDropdown.classList.toggle("show");
                accountButton.setAttribute("aria-expanded", String(isOpen));
            });

            document.addEventListener("click", (event) => {
                if (!accountMenuRoot.contains(event.target)) {
                    closeAccountMenu();
                }
            });

            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape") {
                    closeAccountMenu();
                }
            });

            logoutButton.addEventListener("click", () => {
                ["capsule_user", "capsule_username", "capsule_logged"].forEach((cookieName) => {
                    document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
                });
                window.location.href = "http://capsule.net.tr";
            });
        }

        function setupSidebarToggle() {
            sidebarToggle.addEventListener("click", () => {
                bodyLayout.classList.toggle("sidebar-minimal");
            });
        }

        function renderGames(games) {
            const recommendedGames = games.slice(0, 12);

            recommendedGrid.innerHTML = recommendedGames.length
                ? recommendedGames.map(createGameCard).join("")
                : `<div class="status">Önerilen oyun bulunamadı.</div>`;

            statusBox.textContent = `${games.length} oyun yüklendi.`;
            statusBox.classList.remove("error");
        }

        async function init() {
            const username = getCookie("capsule_username");
            const isLoggedIn = getCookie("capsule_logged") === "true";

            if (isLoggedIn && username) {
                usernameChip.textContent = username;
                profileUrl.href = `profile?username=${encodeURIComponent(username)}`;
            } else {
                accountMenuRoot.classList.add("hidden");
                authLinks.classList.remove("hidden");
                profileUrl.classList.add("hidden");
                avatarNavLink.classList.add("hidden");
            }

            if (isLoggedIn) {
                const avatarRaw = await fetchAvatar(username);
                userAvatar.src = resolveAvatarUrl(username, avatarRaw);
                setupAccountMenu();
            }

            setupSidebarToggle();
            createSkeletonCards(recommendedGrid, 6);

            try {
                const games = await fetchGames();
                renderGames(games);
            } catch (error) {
                recommendedGrid.innerHTML = "";
                statusBox.textContent = `Oyunlar yüklenemedi: ${error.message}`;
                statusBox.classList.add("error");
            }
        }

        document.addEventListener("DOMContentLoaded", init);
    </script>
</body>
</html>