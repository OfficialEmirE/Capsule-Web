<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oyun | Capsule</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --line:#dedede; --text:#202020; --muted:#5f5f5f; --card:#fff; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; font-family:"Nunito","Segoe UI",sans-serif; background:#fff; color:var(--text); }
        .layout { min-height:100vh; display:grid; grid-template-rows:auto 1fr; }
        .topbar { position:sticky; top:0; z-index:40; display:flex; justify-content:space-between; gap:12px; align-items:center; padding:12px 20px; border-bottom:1px solid var(--line); background:#fff; }
        .top-left,.top-right { display:flex; align-items:center; gap:10px; min-width:0; }
        .brand img { height:42px; width:auto; object-fit:contain; }
        .search { position:relative; width:min(460px,55vw); }
        .search input { width:100%; height:38px; border:1px solid var(--line); border-radius:999px; padding:0 14px 0 38px; background:#f8f8f8; color:var(--text); outline:none; }
        .search svg { width:16px; height:16px; position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); }
        .menu-btn,.account-btn,.auth-link { border:1px solid var(--line); border-radius:999px; background:#fff; color:var(--text); font-weight:700; }
        .menu-btn { height:36px; padding:0 10px; cursor:pointer; }
        .auth-link { text-decoration:none; padding:8px 12px; font-size:13px; }
        .auth-link.primary { background:#111; color:#fff; border-color:#111; }
        .account-root { position:relative; }
        .account-btn { display:flex; align-items:center; gap:8px; padding:4px 10px 4px 4px; cursor:pointer; }
        .avatar { width:30px; height:30px; border-radius:50%; border:1px solid var(--line); object-fit:cover; }
        .name { max-width:140px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:14px; }
        .dropdown { display:none; position:absolute; top:calc(100% + 8px); right:0; min-width:190px; border:1px solid var(--line); border-radius:12px; background:#fff; padding:6px; box-shadow:0 10px 25px rgba(0,0,0,.08); }
        .dropdown.show { display:block; }
        .dropdown a,.dropdown button { width:100%; display:block; text-align:left; border:0; border-radius:8px; background:transparent; text-decoration:none; color:var(--text); font-weight:700; padding:9px 10px; cursor:pointer; }
        .dropdown button.logout { color:#dc2626; }
        .dropdown a:hover,.dropdown button:hover { background:#f3f3f3; }
        .body { display:grid; grid-template-columns:220px minmax(0,1fr); min-height:0; }
        .body.min .sidebar { display:none; }
        .body.min { grid-template-columns:1fr; }
        .sidebar { border-right:1px solid var(--line); background:#fafafa; padding:14px; display:grid; align-content:space-between; gap:18px; }
        .nav { display:grid; gap:6px; }
        .nav.bottom { border-top:1px solid var(--line); padding-top:10px; }
        .nav a { text-decoration:none; color:var(--muted); font-weight:700; padding:8px 10px; border-radius:10px; }
        .nav a:hover,.nav a.active { color:var(--text); background:#f1f1f1; }
        .content { padding:18px; overflow:auto; }
        .title { margin:0 0 10px; font-size:30px; font-weight:800; }
        .status { margin:0 0 14px; border:1px solid var(--line); background:#f8f8f8; border-radius:12px; padding:10px 12px; color:var(--muted); font-weight:700; }
        .status.error { border-color:#fecaca; background:#fff1f1; color:#7f1d1d; }
        .grid-top { display:grid; gap:14px; grid-template-columns:minmax(0,2fr) minmax(260px,1fr); }
        .grid-bottom { display:grid; gap:14px; grid-template-columns:minmax(0,2fr) minmax(280px,1fr); margin-top:14px; }
        .card { background:var(--card); border:1px solid #e5e5e5; border-radius:14px; box-shadow:0 4px 14px rgba(0,0,0,.06); overflow:hidden; }
        .media-stage { aspect-ratio:16/9; background:#f5f5f5; border-bottom:1px solid var(--line); }
        .media-stage img,.media-stage video { width:100%; height:100%; display:block; object-fit:cover; }
        .thumbs { display:flex; gap:8px; flex-wrap:wrap; padding:10px; }
        .thumb { width:58px; height:36px; border:1px solid var(--line); border-radius:8px; background:#fff; padding:0; cursor:pointer; overflow:hidden; display:grid; place-items:center; font-weight:800; }
        .thumb.active { box-shadow:inset 0 0 0 1px #111; border-color:#111; }
        .thumb img { width:100%; height:100%; object-fit:cover; }
        .box { padding:14px; display:grid; gap:12px; }
        .gname { margin:0; font-size:30px; line-height:1.1; }
        .gby { margin:0; color:var(--muted); font-weight:700; }
        .play { height:44px; border-radius:10px; background:#111; color:#fff; text-decoration:none; display:flex; align-items:center; justify-content:center; font-weight:800; }
        .h2 { margin:0; font-size:20px; font-weight:800; }
        .desc { margin:0; white-space:pre-wrap; color:#333; line-height:1.5; }
        .details { display:grid; gap:8px; grid-template-columns:repeat(2,minmax(0,1fr)); }
        .detail { border:1px solid var(--line); border-radius:10px; background:#fff; padding:8px 10px; font-size:13px; font-weight:700; color:#444; }
        .switches { border-top:1px solid var(--line); padding-top:10px; display:flex; flex-wrap:wrap; gap:8px; }
        .sw { border:1px solid var(--line); border-radius:999px; background:#fff; padding:6px 10px; font-size:13px; font-weight:800; cursor:pointer; }
        .sw.active { background:#111; color:#fff; border-color:#111; }
        .panel { border:1px solid var(--line); border-radius:10px; background:#f8f8f8; padding:10px; }
        .hidden { display:none !important; }
        @media (max-width:1024px) { .grid-top,.grid-bottom,.details { grid-template-columns:1fr; } }
        @media (max-width:840px) { .body { grid-template-columns:1fr; } .sidebar { border-right:0; border-bottom:1px solid var(--line); } }
    </style>
</head>
<body>
<main class="layout">
    <header class="topbar">
        <div class="top-left">
            <a class="brand" href="/"><img src="/CapsuleLogo.png" alt="Capsule"></a>
            <label class="search">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"></circle><line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" stroke-width="2"></line></svg>
                <input type="text" placeholder="Ara..." disabled>
            </label>
            <button id="menu-btn" class="menu-btn" type="button">Menü</button>
        </div>
        <div class="top-right">
            <div id="auth-links" class="hidden">
                <a class="auth-link" href="/login/login.php">Giriş Yap</a>
                <a class="auth-link primary" href="/login/register.php">Kayıt Ol</a>
            </div>
            <div id="account-root" class="account-root">
                <button id="account-btn" class="account-btn" type="button" aria-expanded="false">
                    <img id="user-avatar" class="avatar" src="https://ui-avatars.com/api/?name=User&background=f3f3f3&color=111111" alt="Avatar">
                    <span id="username-chip" class="name">username</span>
                    <span>▼</span>
                </button>
                <div id="account-dropdown" class="dropdown">
                    <a href="/account/">Hesap Ayarları</a>
                    <button id="logout-btn" class="logout" type="button">Oturumdan Çık</button>
                </div>
            </div>
        </div>
    </header>

    <section id="body" class="body">
        <aside class="sidebar">
            <nav class="nav">
                <a href="/">Ana Sayfa</a>
                <a href="/games/" class="active">Popüler</a>
                <a id="avatar-link" href="/profile/avatar.php">Avatar</a>
                <a href="/studio/">Pazar</a>
            </nav>
            <nav class="nav bottom">
                <a id="profile-link" href="/profile?username">Profil</a>
                <a href="/studio/">Oluştur</a>
                <a href="https://capsule.instatus.com/" target="_blank" rel="noopener">Status</a>
                <a href="https://discord.gg/J4arkFaBnf" target="_blank" rel="noopener">Destek</a>
            </nav>
        </aside>

        <section class="content">
            <h1 class="title">Oyun Detayı</h1>
            <div id="status" class="status">Oyun yükleniyor...</div>
            <div id="game-container"></div>
        </section>
    </section>
</main>

<script>
const API_BASE = "/api/v1/games/";
const serverGameId = <?php echo isset($id) && is_numeric($id) ? json_encode((string)$id) : "''"; ?>;
const statusBox = document.getElementById("status");
const gameContainer = document.getElementById("game-container");
const body = document.getElementById("body");
const menuBtn = document.getElementById("menu-btn");
const authLinks = document.getElementById("auth-links");
const accountRoot = document.getElementById("account-root");
const accountBtn = document.getElementById("account-btn");
const accountDropdown = document.getElementById("account-dropdown");
const userAvatar = document.getElementById("user-avatar");
const usernameChip = document.getElementById("username-chip");
const logoutBtn = document.getElementById("logout-btn");
const profileLink = document.getElementById("profile-link");
const avatarLink = document.getElementById("avatar-link");
let mediaItems = [];

function getCookie(name) { const m = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + "=([^;]*)")); return m ? decodeURIComponent(m[1]) : ""; }
function escapeHtml(v) { return v === null || v === undefined ? "" : String(v).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\"/g,"&quot;").replace(/'/g,"&#039;"); }
function formatDate(v) { const d = new Date(v || ""); return Number.isNaN(d.getTime()) ? "-" : d.toLocaleDateString("tr-TR"); }
function openCustomProtocol(url) { const i = document.createElement("iframe"); i.style.display = "none"; i.src = url; document.body.appendChild(i); setTimeout(() => i.remove(), 100); }

function resolveGameId() {
    const q = new URLSearchParams(window.location.search).get("id");
    const parts = window.location.pathname.split("/").filter(Boolean);
    const p = parts[parts.length - 1] || "";
    const pathId = /^\d+$/.test(p) ? p : "";
    return serverGameId || q || pathId;
}

function parseArrayField(v) {
    if (!v) return [];
    if (Array.isArray(v)) return v;
    if (typeof v !== "string") return [];
    try { const parsed = JSON.parse(v); return Array.isArray(parsed) ? parsed : []; } catch (_e) { return v.split(",").map(x => x.trim()).filter(Boolean); }
}

        function collectMedia(game) {
            const list = [];
    if (game.video_url) list.push({ type: "video", src: game.video_url });
    if (game.image_url) list.push({ type: "image", src: game.image_url });
    parseArrayField(game.gallery || game.media || game.images || game.thumbnails).forEach((src) => {
        if (!src) return;
        const l = String(src).toLowerCase();
        list.push({ type: (l.endsWith(".mp4") || l.endsWith(".webm") || l.includes("video")) ? "video" : "image", src: src });
    });
            const seen = new Set();
            const unique = [];
            list.forEach((x) => {
                if (!seen.has(x.src)) {
                    seen.add(x.src);
                    unique.push(x);
                }
            });
            return unique.length ? unique : [{ type: "image", src: "https://placehold.co/1280x720?text=No+Media" }];
        }

function mediaStage(item, title) {
    if (item.type === "video") return `<video controls preload="metadata"><source src="${escapeHtml(item.src)}"></video>`;
    return `<img src="${escapeHtml(item.src)}" alt="${escapeHtml(title)}" onerror="this.src='https://placehold.co/1280x720?text=Media+Error'">`;
}

function bindUI(title) {
    document.querySelectorAll("[data-media-index]").forEach((t) => {
        t.addEventListener("click", () => {
            const i = Number(t.getAttribute("data-media-index"));
            const stage = document.getElementById("media-stage");
            const item = mediaItems[i];
            if (!stage || !item) return;
            stage.innerHTML = mediaStage(item, title);
            document.querySelectorAll("[data-media-index]").forEach((x) => x.classList.remove("active"));
            t.classList.add("active");
        });
    });
    document.querySelectorAll("[data-switch]").forEach((b) => {
        b.addEventListener("click", () => {
            const key = b.getAttribute("data-switch");
            document.querySelectorAll("[data-switch]").forEach((x) => x.classList.remove("active"));
            b.classList.add("active");
            document.querySelectorAll("[data-panel]").forEach((p) => p.classList.toggle("hidden", p.getAttribute("data-panel") !== key));
        });
    });
}

async function fetchAvatar(username) {
    if (!username) return "";
    const r = await fetch(`/api/v1/avatar/?type=get&username=${encodeURIComponent(username)}`, { headers: { Accept: "application/json" }, cache: "no-store" });
    if (!r.ok) return "";
    const p = await r.json();
    return p && p.status === "success" ? (p.avatar || "") : "";
}

function resolveAvatar(username, raw) {
    const fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(username || "User")}&background=f3f3f3&color=111111`;
    if (!raw) return fallback;
    if (raw.startsWith("http://") || raw.startsWith("https://") || raw.startsWith("data:image/")) return raw;
    try {
        const j = JSON.parse(raw);
        if (typeof j.faceURL === "string" && j.faceURL) return j.faceURL;
        if (Array.isArray(j.avatars) && typeof j.avatars[0] === "string") return j.avatars[0];
    } catch (_e) {}
    return fallback;
}

async function fetchGame(id) {
    const r = await fetch(`${API_BASE}?id=${encodeURIComponent(id)}`, { headers: { Accept: "application/json" }, cache: "no-store" });
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    const p = await r.json();
    if (!p || p.status !== "success" || !p.data || typeof p.data !== "object") throw new Error((p && p.message) ? p.message : "Oyun verisi alınamadı");
    return p.data;
}

function renderGame(game) {
    const name = game.title || "Oyunun Adı";
    const by = game.username || "Bilinmeyen";
    const desc = game.description || "Açıklama yok.";
    const players = game.players ?? 0;
    const mostPlayed = game.most_played ?? game.visits ?? 0;
    const createDate = (game.hide_create_date === true || game.hide_create_date === 1 || game.hide_create_date === "1" || game.show_create_date === false) ? "Gizli" : formatDate(game.created_at);
    const releaseDate = formatDate(game.release_date || game.created_at);
    const updateDate = formatDate(game.updated_at);
    const maxPlayer = game.max_players ?? game.maxplayer ?? "-";
    const genre = game.genre || game.type || game.category || "-";
    const systemReq = game.system_requirements || "Sistem gereksinimi belirtilmemiş.";
    const store = game.store_info || "Bu oyun için mağaza bilgisi henüz yok.";

    mediaItems = collectMedia(game);
    const thumbs = mediaItems.map((m, i) => `<button type="button" class="thumb ${i === 0 ? "active" : ""}" data-media-index="${i}">${m.type === "image" ? `<img src="${escapeHtml(m.src)}" alt="thumb">` : "▶"}</button>`).join("");

    gameContainer.innerHTML = `
        <div class="grid-top">
            <article class="card"><div id="media-stage" class="media-stage">${mediaStage(mediaItems[0], name)}</div><div class="thumbs">${thumbs}</div></article>
            <aside class="card box"><div><h2 class="gname">${escapeHtml(name)}</h2><p class="gby">${escapeHtml(by)} tarafından</p></div><a class="play" href="javascript:openCustomProtocol('capsule://open/${escapeHtml(game.id || "")}')">Oyuna Gir</a></aside>
        </div>
        <div class="grid-bottom">
            <article class="card box">
                <h3 class="h2">Açıklama</h3><p class="desc">${escapeHtml(desc)}</p>
                <h3 class="h2">Detaylar</h3>
                <div class="details">
                    <div class="detail">Oyuncular: ${escapeHtml(players)}</div>
                    <div class="detail">En Çok Oynanma: ${escapeHtml(mostPlayed)}</div>
                    <div class="detail">Oluşturma Tarihi: ${escapeHtml(createDate)}</div>
                    <div class="detail">Yayın Tarihi: ${escapeHtml(releaseDate)}</div>
                    <div class="detail">Güncelleme Tarihi: ${escapeHtml(updateDate)}</div>
                    <div class="detail">Maks Oyuncu: ${escapeHtml(maxPlayer)}</div>
                    <div class="detail">Tür: ${escapeHtml(genre)}</div>
                    <div class="detail">Şikayet: <a href="/report.php?id=${escapeHtml(game.id || "")}">Oyunu Bildir</a></div>
                </div>
                <div class="switches">
                    <button class="sw" type="button" data-switch="servers">Sunucular</button>
                    <button class="sw" type="button" data-switch="suggest">Öneriler</button>
                    <button class="sw" type="button" data-switch="req">Sistem Gereksinimi</button>
                    <button class="sw active" type="button" data-switch="store">Mağaza</button>
                </div>
                <div class="panel hidden" data-panel="servers">Bu bölüm gizli.</div>
                <div class="panel hidden" data-panel="suggest">Bu bölüm gizli.</div>
                <div class="panel hidden" data-panel="req">${escapeHtml(systemReq)}</div>
                <div class="panel" data-panel="store">${escapeHtml(store)}</div>
            </article>
            <aside class="card box"><h3 class="h2">g_MoreDetails</h3><p class="desc">g_Servers | g_Öneri | g_SistemGereksinimi | g_Mağaza</p></aside>
        </div>
    `;

    bindUI(name);
    statusBox.textContent = "Oyun yüklendi.";
    statusBox.classList.remove("error");
}

async function init() {
    const username = getCookie("capsule_username");
    const isLoggedIn = getCookie("capsule_logged") === "true";
    if (isLoggedIn && username) {
        usernameChip.textContent = username;
        profileLink.href = `/profile?username=${encodeURIComponent(username)}`;
        userAvatar.src = resolveAvatar(username, await fetchAvatar(username));
    } else {
        accountRoot.classList.add("hidden");
        authLinks.classList.remove("hidden");
        profileLink.classList.add("hidden");
        avatarLink.classList.add("hidden");
    }

    menuBtn.addEventListener("click", () => body.classList.toggle("min"));
    accountBtn.addEventListener("click", (e) => { e.stopPropagation(); accountDropdown.classList.toggle("show"); accountBtn.setAttribute("aria-expanded", accountDropdown.classList.contains("show") ? "true" : "false"); });
    document.addEventListener("click", (e) => { if (!accountRoot.contains(e.target)) accountDropdown.classList.remove("show"); });
    logoutBtn.addEventListener("click", () => { ["capsule_user","capsule_username","capsule_logged"].forEach((k) => { document.cookie = `${k}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`; }); window.location.href = "/"; });

    try {
        const gameId = resolveGameId();
        if (!gameId) throw new Error("Geçerli oyun kimliği bulunamadı.");
        renderGame(await fetchGame(gameId));
    } catch (err) {
        statusBox.textContent = `Hata: ${err.message}`;
        statusBox.classList.add("error");
    }
}

document.addEventListener("DOMContentLoaded", init);
</script>
</body>
</html>
