<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avatar Ayarı - Capsule</title>
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
        .content{padding:24px}
        .card{max-width:520px;border:1px solid var(--line);border-radius:14px;padding:18px}
        .field{margin-bottom:12px}.field label{display:block;font-size:14px;font-weight:700;color:var(--muted);margin-bottom:6px}
        .field input{width:100%;height:42px;padding:8px;border:1px solid var(--line);border-radius:10px}
        .btn{border:1px solid #111;background:#111;color:#fff;border-radius:10px;padding:10px 14px;font-weight:800;cursor:pointer}
        @media(max-width:840px){.body{grid-template-columns:1fr}.sidebar{border-right:0;border-bottom:1px solid var(--line);display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}}
    </style>
</head>
<body>
    <main class="layout">
        <header class="topbar"><a class="brand" href="../home.php"><img src="../CapsuleLogo.png" alt="Capsule"></a></header>
        <section class="body">
            <aside class="sidebar">
                <a href="../home.php">Ana Sayfa</a>
                <a href="../games/">Popüler</a>
                <a href="index.php">Profil</a>
                <a href="avatar.php" class="active">Avatar</a>
            </aside>
            <section class="content">
                <div class="card">
                    <h1>Avatar Ayarı</h1>
                    <form onsubmit="updateAvatar(event)">
                        <div class="field">
                            <label for="avatar">Avatar Rengi</label>
                            <input type="color" id="avatar" name="avatar" required>
                        </div>
                        <button type="submit" class="btn">Güncelle</button>
                    </form>
                </div>
            </section>
        </section>
    </main>

<script>
    const cookiemanager = import("../login/cookiemanager.js");

    async function isLogged() {
        const cookieModule = await cookiemanager;
        return cookieModule.isCookieValid("capsule_logged");
    }

    isLogged().then((value) => {
        if (!value) {
            window.location.href = "http://capsule.net.tr/login/login.php?href=" + encodeURIComponent(window.location.href);
        }
    });

    async function updateAvatar(event) {
        event.preventDefault();
        const color = document.getElementById("avatar").value;
        const cookieModule = await cookiemanager;
        const apikey = cookieModule.getCookie("capsule_user");

        const jsonData = { color: String(color), avatars: [] };

        const response = await fetch("http://capsule.net.tr/api/v1/avatar/?type=set&apikey=" + apikey + "&data=" + encodeURIComponent(JSON.stringify(jsonData)), {
            method: "GET",
            headers: { "Content-Type": "application/json" }
        });

        const data = await response.json();
        if (data.status !== "success") throw new Error(data.message || "Avatar güncellenemedi.");
        alert("Avatar güncellendi!");
    }
</script>
</body>
</html>