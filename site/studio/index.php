<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Capsule Studio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
  <link rel="icon" href="../favicon.ico" type="image/x-icon">
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
      --surface: #fafafa;
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
      filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.2));
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

    .top-right {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      flex-shrink: 0;
    }

    .account-menu {
      position: relative;
    }

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

    .dropdown.show {
      display: block;
    }

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
    .dropdown button:hover {
      background: #f3f3f3;
    }`r`n`r`n    .dropdown .logout-btn { color: #dc2626; }`r`n    .dropdown .logout-btn:hover { background: #fef2f2; }

    .body {
      display: grid;
      grid-template-columns: clamp(190px, 20vw, 244px) minmax(0, 1fr);
      min-height: 0;
      overflow: hidden;
    }

    .sidebar {
      border-right: 1px solid var(--line);
      padding: clamp(12px, 1.7vw, 20px);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 26px;
      background: var(--surface);
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

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 14px;
    }

    .page-title {
      margin: 0;
      font-size: clamp(26px, 3.5vw, 44px);
      font-weight: 800;
    }

    .create-btn {
      border: 1px solid #111;
      background: #111;
      color: #fff;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
    }

    .create-btn:hover {
      background: #000;
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

    .studio-game {
      display: grid;
      grid-template-columns: minmax(190px, 320px) minmax(0, 1fr) auto;
      gap: 14px;
      align-items: center;
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: 14px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      margin-bottom: 14px;
    }

    .studio-thumb {
      aspect-ratio: 16 / 9;
      width: 100%;
      background: #f3f3f3;
      overflow: hidden;
    }

    .studio-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .studio-info {
      padding: 12px 0;
      min-width: 0;
    }

    .studio-info h3 {
      margin: 0 0 6px;
      font-size: 20px;
      line-height: 1.15;
    }

    .studio-info p {
      margin: 0;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.4;
    }

    .studio-actions {
      display: flex;
      flex-direction: column;
      gap: 8px;
      padding: 12px;
    }

    .btn {
      text-decoration: none;
      border: 1px solid #111;
      border-radius: 9px;
      padding: 8px 12px;
      font-size: 13px;
      font-weight: 800;
      text-align: center;
      cursor: pointer;
      background: #fff;
      color: #111;
    }

    .btn.play {
      background: #2563eb;
      border-color: #2563eb;
      color: #fff;
    }

    .btn.delete {
      background: #dc2626;
      border-color: #dc2626;
      color: #fff;
    }

    .btn:hover {
      opacity: 0.92;
    }

    .loading {
      text-align: center;
      padding: 18px;
      color: var(--muted);
      font-weight: 700;
    }

    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.35);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 100;
      padding: 12px;
    }

    .modal-backdrop.show {
      display: flex;
    }

    .modal {
      width: min(420px, 96vw);
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 16px;
    }

    .modal h3 {
      margin: 0 0 10px;
      font-size: 22px;
    }

    .field {
      margin-bottom: 10px;
    }

    .field label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 6px;
      color: var(--muted);
    }

    .field input,
    .field textarea {
      width: 100%;
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 10px;
      font-size: 14px;
      outline: none;
      font-family: inherit;
    }

    .modal-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-top: 8px;
    }

    .hidden {
      display: none !important;
    }

    @media (max-width: 1040px) {
      .studio-game {
        grid-template-columns: 1fr;
      }

      .studio-info {
        padding: 0 12px;
      }

      .studio-actions {
        flex-direction: row;
        flex-wrap: wrap;
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

      .nav-group,
      .nav-group.bottom {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }

    @media (max-width: 640px) {
      .topbar {
        flex-wrap: wrap;
      }

      .top-left,
      .top-right {
        width: 100%;
      }

      .nav-group,
      .nav-group.bottom {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .page-header {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>

<body>
  <main class="layout">
    <header class="topbar">
      <div class="top-left">
        <a class="brand" href="../index.php" aria-label="Capsule ana sayfa">
          <img src="../CapsuleStudioLogo.png" alt="Capsule Studio">
        </a>
        <label class="search-box" aria-label="Arama kutusu">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" stroke-width="2"></line>
          </svg>
          <input type="text" placeholder="Ara..." disabled>
        </label>
      </div>

      <div class="top-right">
        <div class="account-menu" id="account-menu-root">
          <button class="account-button" id="account-button" type="button" aria-haspopup="true" aria-expanded="false">
            <img class="account-avatar" id="user-avatar" src="https://ui-avatars.com/api/?name=User&background=f3f3f3&color=111111" alt="Kullanıcı avatarı">
            <span class="account-name" id="username-chip">username</span>
            <span class="account-arrow" aria-hidden="true">▼</span>
          </button>
          <div class="dropdown" id="account-dropdown">
            <a href="../account/">Hesap Ayarları</a>
            <button id="logout-button" class="logout-btn" type="button">Oturumdan Çık</button>
          </div>
        </div>
      </div>
    </header>

    <section class="body">
      <aside class="sidebar" aria-label="Yan menu">
        <nav class="nav-group">
          <a href="index.php">Ana Sayfa</a>
          <a href="../games/">Oyunlarım</a>
        </nav>

        <nav class="nav-group bottom">
          <a href="../account/">Ayarlar</a>
          <a href="../index.php">Capsule Dön</a>
          <a href="https://discord.gg/jMmZZjQxk8" target="_blank" rel="noopener">Destek</a>
        </nav>
      </aside>

      <section class="content">
        <div class="page-header">
          <h1 class="page-title">Studio</h1>
          <button onclick="openCreateGameModal()" class="create-btn">Yeni Oyun Oluştur</button>
        </div>

        <div id="error" class="status error hidden"></div>
        <div id="warning" class="status hidden"></div>

        <div id="games-container"></div>
        <div id="loading" class="loading">Oyunlar yükleniyor...</div>
      </section>
    </section>
  </main>

  <div id="createGameModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="create-title">
    <div class="modal">
      <form onsubmit="createGame(event)">
        <h3 id="create-title">Yeni Oyun Oluştur</h3>

        <div class="field">
          <label for="title">Oyun Adı</label>
          <input type="text" id="title" name="title" required maxlength="100" />
        </div>

        <div class="field">
          <label for="description">Açıklama</label>
          <textarea id="description" name="description" maxlength="100"></textarea>
        </div>

        <div class="field">
          <label for="image_url">Görsel URL</label>
          <input type="url" id="image_url" name="image_url" placeholder="https://..." />
        </div>

        <div class="modal-actions">
          <button type="submit" class="btn play">Oluştur</button>
          <button type="button" class="btn" onclick="closeCreateGameModal()">Kapat</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const cookiemanager = import("../login/cookiemanager.js");

    const usernameChip = document.getElementById("username-chip");
    const userAvatar = document.getElementById("user-avatar");
    const accountMenuRoot = document.getElementById("account-menu-root");
    const accountButton = document.getElementById("account-button");
    const accountDropdown = document.getElementById("account-dropdown");
    const logoutButton = document.getElementById("logout-button");

    function openCreateGameModal() {
      document.getElementById("createGameModal").classList.add("show");
    }

    function closeCreateGameModal() {
      document.getElementById("createGameModal").classList.remove("show");
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
          closeCreateGameModal();
        }
      });

      logoutButton.addEventListener("click", () => {
        ["capsule_user", "capsule_username", "capsule_logged"].forEach((cookieName) => {
          document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
        });
        window.location.href = "../index.php";
      });
    }

    async function isLogged() {
      const cookieModule = await cookiemanager;
      return cookieModule.isCookieValid("capsule_logged");
    }

    async function loadGames() {
      const container = document.getElementById("games-container");
      const loading = document.getElementById("loading");
      const username = (await cookiemanager).getCookie("capsule_username");

      try {
        const response = await fetch("../api/v1/games/?username=" + encodeURIComponent(username));
        const data = await response.json();

        if (data.status !== "success") {
          throw new Error(data.message || "Veri alınamadı.");
        }

        loading.style.display = "none";

        container.innerHTML = data.data.map((game) => `
          <article class="studio-game">
            <div class="studio-thumb">
              <img src="${game.image_url || "https://placehold.co/800x450?text=Capsule"}" alt="${game.title}"
                onerror="this.src='https://placehold.co/800x450?text=Capsule'" />
            </div>

            <div class="studio-info">
              <h3>${game.title}</h3>
              <p>${game.description || "Açıklama yok."}</p>
            </div>

            <div class="studio-actions">
              <a href="/game.php?id=${game.id}" class="btn play">Oyna</a>
              <button onclick="deleteGame(${game.id})" class="btn delete">Sil</button>
              <button onclick="editGame(${game.id})" class="btn">Düzenle</button>
            </div>
          </article>
        `).join("");
      } catch (error) {
        if (error.message === "Game not found") {
          loading.textContent = "Oyununuz yok.";
        } else {
          loading.innerHTML = `<div class="status error" style="display:inline-block">Hata: ${error.message}</div>`;
        }
      }
    }

    async function deleteGame(id) {
      if (!confirm("Bu oyunu silmek istediğinize emin misiniz?")) {
        return;
      }

      const cookieModule = await cookiemanager;
      const apikey = cookieModule.getCookie("capsule_user");

      try {
        const response = await fetch("../api/v1/games/delete.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id, apikey })
        });

        const data = await response.json();
        if (data.status === "success") {
          loadGames();
        } else {
          const errorElement = document.getElementById("error");
          errorElement.textContent = "Silme işlemi başarısız: " + data.message;
          errorElement.classList.remove("hidden");
        }
      } catch (error) {
        const errorElement = document.getElementById("error");
        errorElement.textContent = "Bir hata oluştu: " + error.message;
        errorElement.classList.remove("hidden");
      }
    }

    async function sendCreateGame(apiKey, gameName, desc, image_url) {
      const response = await fetch("../api/v1/games/create.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          apiKey: apiKey,
          title: gameName,
          desc: desc,
          image_url: image_url
        })
      });

      if (!response.ok) {
        const text = await response.text();
        try {
          const json = JSON.parse(text);
          throw new Error(json.message || "Server error: " + response.status);
        } catch (_e) {
          throw new Error("Server error (" + response.status + "): " + text.substring(0, 100));
        }
      }

      return await response.json();
    }

    async function createGame(event) {
      if (event) {
        event.preventDefault();
      }

      const cookieModule = await cookiemanager;
      const apiKey = cookieModule.getCookie("capsule_user");
      const gameName = document.getElementById("title").value;
      const description = document.getElementById("description").value;
      const image_url = document.getElementById("image_url").value;

      try {
        const data = await sendCreateGame(apiKey, gameName, description, image_url);

        if (data.status === "success") {
          closeCreateGameModal();
          loadGames();
          if (event) {
            event.target.reset();
          }
        } else {
          alert("Hata: " + (data.message || "Bilinmeyen hata"));
        }
      } catch (e) {
        alert("Bir hata oluştu: " + e.message);
      }
    }

    function openCustomProtocol(url) {
      const iframe = document.createElement("iframe");
      iframe.style.display = "none";
      iframe.src = url;
      document.body.appendChild(iframe);
      setTimeout(() => {
        document.body.removeChild(iframe);
      }, 100);
    }

    function editGame(id) {
      openCustomProtocol(`capsule://studio/${id}`);
    }

    isLogged().then((value) => {
      if (!value) {
        window.location.href = "../login/login.php?href=" + encodeURIComponent(window.location.href);
      }
    });

    window.addEventListener("DOMContentLoaded", async () => {
      const cookieModule = await cookiemanager;
      const username = cookieModule.getCookie("capsule_username") || "User";
      usernameChip.textContent = username;
      userAvatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(username)}&background=f3f3f3&color=111111`;
      setupAccountMenu();
      loadGames();
    });
  </script>
</body>
</html>