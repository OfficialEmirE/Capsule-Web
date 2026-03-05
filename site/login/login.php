<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Capsule</title>
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Nunito", "Segoe UI", sans-serif;
            background: linear-gradient(180deg, #f4f7fb 0%, #e8eef7 100%);
            color: #1f2937;
            display: grid;
            place-items: center;
            padding: 20px;
        }
        .auth-shell {
            width: min(420px, 100%);
        }
        .brand {
            text-align: center;
            margin-bottom: 16px;
        }
        .brand img {
            height: 46px;
            width: auto;
        }
        .card {
            background: #fff;
            border: 1px solid #d8e1ef;
            border-radius: 8px;
            padding: 22px;
            box-shadow: 0 6px 22px rgba(38, 59, 94, 0.12);
        }
        h1 {
            margin: 0 0 14px;
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            color: #2f3d52;
        }
        .input {
            width: 100%;
            height: 42px;
            border: 1px solid #b8c7dd;
            border-radius: 4px;
            padding: 0 10px;
            font-size: 14px;
            margin-bottom: 10px;
            background: #fff;
        }
        .input:focus {
            outline: 2px solid #8bb5ff;
            border-color: #5e92e4;
        }
        .btn {
            width: 100%;
            border: 1px solid #2f66c8;
            background: linear-gradient(180deg, #4f8df2 0%, #2f66c8 100%);
            color: #fff;
            border-radius: 4px;
            height: 42px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
        }
        .btn:hover { filter: brightness(1.03); }
        .muted {
            text-align: center;
            color: #5c6f8f;
            font-size: 14px;
            margin-top: 14px;
        }
        .muted a {
            color: #2f66c8;
            font-weight: 800;
            text-decoration: none;
        }
        .message {
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 700;
        }
        .message.error { background: #fff1f1; color: #9f1239; border: 1px solid #fecdd3; }
        .message.ok { background: #ecfdf3; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <main class="auth-shell">
        <div class="brand"><a href="../home.php"><img src="../CapsuleLogo.png" alt="Capsule"></a></div>
        <section class="card">
            <h1>Giriş Yap</h1>
            <div id="message"></div>
            <form id="loginForm">
                <input class="input" type="text" name="username" placeholder="Kullanıcı adı" required>
                <input class="input" type="password" name="password" placeholder="Şifre" required>
                <button class="btn" type="submit">Giriş Yap</button>
            </form>
            <p class="muted">Hesabın yok mu? <a href="register.php">Kayıt ol</a></p>
        </section>
    </main>

    <script>
        const cookiemanager = import("./cookiemanager.js");
        const params = new URLSearchParams(window.location.search);
        const href = params.get("href");

        async function login(username, pass) {
            const url = "http://capsule.net.tr/api/v1/account/login.php?username=" + encodeURIComponent(username) + "&password=" + encodeURIComponent(pass);
            const response = await fetch(url);
            const text = await response.text();
            try { return JSON.parse(text); }
            catch { return { status: "error", message: "Geçersiz JSON" }; }
        }

        document.getElementById("loginForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const username = e.target.username.value.trim();
            const pass = e.target.password.value;
            const msg = document.getElementById("message");

            const loginData = await login(username, pass);
            if (loginData.status !== "success") {
                msg.innerHTML = `<div class="message error">${loginData.message}</div>`;
                return;
            }

            const cookieModule = await cookiemanager;
            cookieModule.setCookie("capsule_user", loginData.user.apikey, 30);
            cookieModule.setCookie("capsule_username", loginData.user.username, 30);
            cookieModule.setCookie("capsule_logged", "true", 30);

            msg.innerHTML = `<div class="message ok">Giriş başarılı!</div>`;
            setTimeout(() => { window.location.href = href || "http://capsule.net.tr/home.php"; }, 700);
        });
    </script>
</body>
</html>