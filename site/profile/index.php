<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Capsule</title>
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --line:#dedede; --text:#202020; --muted:#5f5f5f; }
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;font-family:"Nunito","Segoe UI",sans-serif;color:var(--text);background:#fff}
        .layout{min-height:100vh;display:grid;grid-template-rows:auto 1fr}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--line)}
        .brand img{height:44px}
        .body{display:grid;grid-template-columns:220px minmax(0,1fr);min-height:0}
        .sidebar{border-right:1px solid var(--line);padding:14px;background:#fafafa;display:flex;flex-direction:column;gap:8px}
        .sidebar a{text-decoration:none;color:var(--muted);font-weight:700;padding:8px 10px;border-radius:10px}
        .sidebar a.active,.sidebar a:hover{color:var(--text);background:#f1f1f1}
        .content{padding:24px;overflow:auto}
        .status{padding:10px 12px;border:1px solid var(--line);border-radius:12px;color:var(--muted);margin-bottom:14px}
        .profile-head{display:flex;gap:16px;align-items:center;margin-bottom:18px}
        .avatar{width:96px;height:96px;border-radius:50%;object-fit:cover;border:1px solid var(--line)}
        .name{margin:0;font-size:28px}
        .games-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
        .card{display:block;text-decoration:none;color:inherit;border:1px solid var(--line);border-radius:12px;overflow:hidden}
        .thumb{aspect-ratio:16/9;background:#f3f3f3}.thumb img{width:100%;height:100%;object-fit:cover}
        .meta{padding:10px}.meta h3{margin:0;font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        @media(max-width:1040px){.games-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:840px){.body{grid-template-columns:1fr}.sidebar{border-right:0;border-bottom:1px solid var(--line);display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:640px){.games-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <main class="layout">
        <header class="topbar"><a class="brand" href="../home.php"><img src="../CapsuleLogo.png" alt="Capsule"></a></header>
        <section class="body">
            <aside class="sidebar">
                <a href="../home.php">Ana Sayfa</a>
                <a href="../games/">Popüler</a>
                <a href="index.php" class="active">Profil</a>
                <a href="avatar.php">Avatar</a>
            </aside>
            <section class="content">
                <div id="status" class="status">Profil yükleniyor...</div>
                <div id="profile" class="hidden">
                    <div class="profile-head">
                        <img id="profile-avatar" class="avatar" src="../WebB.png" alt="Avatar">
                        <div>
                            <h1 id="profile-username" class="name"></h1>
                            <p style="margin:0;color:var(--muted)">Capsule kullanıcısı</p>
                        </div>
                    </div>
                    <h2 style="margin:0 0 10px">Oyunlar</h2>
                    <div id="games-grid" class="games-grid"></div>
                </div>
            </section>
        </section>
    </main>
<script>
    const params = new URLSearchParams(window.location.search);
    const username = params.get("username");
    const avatarUrl = `../api/v1/avatar/?type=get&username=${encodeURIComponent(username || "")}`;
    const gamesUrl = `../api/v1/games/?username=${encodeURIComponent(username || "")}`;

    const dom = {
      status: document.getElementById("status"),
      profile: document.getElementById("profile"),
      avatar: document.getElementById("profile-avatar"),
      username: document.getElementById("profile-username"),
      gamesGrid: document.getElementById("games-grid")
    };

    function escapeHtml(text) {
      if (!text) return "";
      return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function resolveAvatarUrl(raw, fallbackName) {
      const fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(fallbackName || "User")}&background=f3f3f3&color=111111`;
      if (!raw) return fallback;
      if (raw.startsWith("http://") || raw.startsWith("https://") || raw.startsWith("data:image/")) return raw;
      try {
        const parsed = JSON.parse(raw);
        if (parsed.faceURL && typeof parsed.faceURL === "string") return parsed.faceURL;
        if (Array.isArray(parsed.avatars) && parsed.avatars.length > 0 && typeof parsed.avatars[0] === "string") return parsed.avatars[0];
        if (parsed.color && typeof parsed.color === "string") return `https://ui-avatars.com/api/?name=${encodeURIComponent(fallbackName || "User")}&background=${encodeURIComponent(parsed.color.replace("#", ""))}&color=ffffff`;
      } catch (_e) { return fallback; }
      return fallback;
    }

    async function initProfile() {
      if (!username) {
        dom.status.textContent = "Kullanıcı adı belirtilmedi.";
        return;
      }

      try {
        const avRes = await fetch(avatarUrl);
        const avJson = await avRes.json();
        if (avJson.status === "error" && avJson.message && avJson.message.includes("User not found")) {
          dom.status.textContent = "Kullanıcı bulunamadı.";
          return;
        }

        dom.avatar.src = resolveAvatarUrl(avJson.avatar || "", username);
        dom.username.textContent = username;

        const gamesRes = await fetch(gamesUrl);
        const gamesJson = await gamesRes.json();

        if (gamesJson.status === "success" && Array.isArray(gamesJson.data) && gamesJson.data.length > 0) {
          dom.gamesGrid.innerHTML = gamesJson.data.map((game) => `
            <a href="../game.php?id=${game.id}" class="card">
              <div class="thumb"><img src="${game.image_url || "https://placehold.co/640x360?text=Capsule"}" alt="${escapeHtml(game.title)}" onerror="this.src='https://placehold.co/640x360?text=Capsule'"></div>
              <div class="meta"><h3 title="${escapeHtml(game.title)}">${escapeHtml(game.title)}</h3></div>
            </a>
          `).join("");
        } else {
          dom.gamesGrid.innerHTML = `<div class="status" style="grid-column:1/-1">Bu kullanıcının henüz oyunu yok.</div>`;
        }

        dom.status.classList.add("hidden");
        dom.profile.classList.remove("hidden");
      } catch (_e) {
        dom.status.textContent = "Profil yüklenirken bir sorun oluştu.";
      }
    }

    document.addEventListener("DOMContentLoaded", initProfile);
</script>
</body>
</html>