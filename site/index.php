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
            --bg0: #ffffff;
            --bg1: #f7f7f7;
            --bg2: #efefef;
            --line: #dedede;
            --card: #ffffff;
            --card-border: #e5e5e5;
            --text: #202020;
            --muted: #5f5f5f;
            --accent: #2c7be5;
            --accent-soft: #f3f7ff;
            --ok: #2fce7f;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Nunito", "Segoe UI", sans-serif;
            color: var(--text);
            background: #ffffff;
        }

        .layout {
            min-height: 100vh;
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

        .brand {
            display: inline-flex;
            align-items: center;
            height: clamp(36px, 4.1vw, 52px);
        }

        .brand img {
            height: 100%;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.35));
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: clamp(8px, 1.5vw, 14px);
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            background: #ffffff;
            color: var(--text);
            border-radius: 12px;
            min-height: 34px;
            padding: 0 11px;
            font-size: clamp(12px, 1.6vw, 14px);
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .icon-chip {
            width: 34px;
            min-width: 34px;
            padding: 0;
            color: var(--text);
        }

        .avatar-chip {
            width: 34px;
            min-width: 34px;
            padding: 0;
            border-radius: 50%;
            font-size: 16px;
        }

        .body {
            display: grid;
            grid-template-columns: clamp(190px, 20vw, 244px) minmax(0, 1fr);
            min-height: 0;
        }

        .sidebar {
            border-right: 1px solid var(--line);
            padding: clamp(12px, 1.7vw, 20px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 26px;
            background: #fafafa;
        }

        .nav-group {
            display: grid;
            gap: 6px;
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
            color: #ffd2d2;
            background: rgba(80, 19, 19, 0.45);
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

        .hidden {
            display: none !important;
        }

        @media (max-width: 1040px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 840px) {
            .body {
                grid-template-columns: 1fr;
            }

            .sidebar {
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .nav-group {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .nav-group.bottom {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .cards {
                grid-template-columns: 1fr;
            }

            .nav-group,
            .nav-group.bottom {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .top-right {
                width: 100%;
            }

            .chip.username {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="layout">
        <header class="topbar">
            <a class="brand" href="index.php" aria-label="Capsule ana sayfa">
                <img src="CapsuleLogo.png" alt="Capsule Logo" id="c-icon">
            </a>

            <div class="top-right">
                <span class="chip">money</span>
                <span class="chip icon-chip" aria-hidden="true">S</span>
                <span class="chip username" id="username-chip">username</span>
                <span class="chip avatar-chip" aria-hidden="true">U</span>
            </div>
        </header>

        <section class="body">
            <aside class="sidebar" aria-label="Yan menu">
                <nav class="nav-group">
                    <a href="index.php" class="active">Ana Sayfa</a>
                    <a href="games/">Populer</a>
                    <a href="profile/avatar.php">Avatar</a>
                    <a href="studio/">Pazar</a>
                </nav>

                <nav class="nav-group bottom">
                    <a href="account/">Ayarlar</a>
                    <a href="studio/">Olustur</a>
                    <a href="https://discord.gg/J4arkFaBnf" target="_blank" rel="noopener">Destek</a>
                </nav>
            </aside>

            <section class="content">
                <h1 class="page-title">Ana Sayfa</h1>

                <div id="status" class="status">Oyunlar yukleniyor...</div>

                <div class="section-head">
                    <h2>Devam Et</h2>
                </div>
                <div id="continue-grid" class="cards"></div>

                <div class="section-head">
                    <h2>Onerilenler</h2>
                </div>
                <div id="recommended-grid" class="cards"></div>
            </section>
        </section>
    </main>

    <script>
        const API_URL = "api/v1/games/";

        const continueGrid = document.getElementById("continue-grid");
        const recommendedGrid = document.getElementById("recommended-grid");
        const statusBox = document.getElementById("status");
        const usernameChip = document.getElementById("username-chip");

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
            const gameName = game && game.title ? game.title : "Oyunun Adi";
            const gameImage = game && game.image_url ? game.image_url : "";
            const gameId = game && game.id ? String(game.id) : "";
            const gameHref = gameId ? `game.php?id=${encodeURIComponent(gameId)}` : "#";
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
                throw new Error((payload && payload.message) ? payload.message : "Gecersiz API cevabi");
            }

            return payload.data;
        }

        function renderGames(games) {
            const continueGames = games.slice(0, 3);
            const recommendedGames = games.slice(3, 12);

            continueGrid.innerHTML = continueGames.length
                ? continueGames.map(createGameCard).join("")
                : `<div class="status">Devam Et bolumunde oyun yok.</div>`;

            const fallbackList = recommendedGames.length ? recommendedGames : continueGames;
            recommendedGrid.innerHTML = fallbackList.length
                ? fallbackList.map(createGameCard).join("")
                : `<div class="status">Onerilen oyun bulunamadi.</div>`;

            statusBox.textContent = `${games.length} oyun yuklendi.`;
            statusBox.classList.remove("error");
        }

        async function init() {
            const username = getCookie("capsule_username");
            if (username) {
                usernameChip.textContent = username;
            }

            createSkeletonCards(continueGrid, 3);
            createSkeletonCards(recommendedGrid, 6);

            try {
                const games = await fetchGames();
                renderGames(games);
            } catch (error) {
                continueGrid.innerHTML = "";
                recommendedGrid.innerHTML = "";
                statusBox.textContent = `Oyunlar yuklenemedi: ${error.message}`;
                statusBox.classList.add("error");
            }
        }

        document.addEventListener("DOMContentLoaded", init);
    </script>
</body>
</html>
