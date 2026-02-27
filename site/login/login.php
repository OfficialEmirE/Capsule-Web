<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Giriş Yap - Capsule</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">


    <div class="bg-white p-8 rounded shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold mb-4 text-center">Giriş Yap</h1>

        <div id="message"></div>

        <form id="loginForm">
            <input type="text" name="username" placeholder="Kullanıcı adı" class="w-full p-2 border rounded mb-3"
                required>

            <input type="password" name="password" placeholder="Şifre" class="w-full p-2 border rounded mb-3" required>

            <button class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">
                Giriş Yap
            </button>
        </form>

        <p class="mt-4 text-center text-sm">
            Hesabın yok mu? <a href="register.php" class="text-indigo-600">Kayıt ol</a>
        </p>
    </div>


    <script>
        const cookiemanager = import("./cookiemanager.js");

        const params = new URLSearchParams(window.location.search);
        const href = params.get('href');

        async function login(username, pass) {
            let url = "http://capsule.net.tr/api/v1/account/login.php?username="
                + encodeURIComponent(username)
                + "&password="
                + encodeURIComponent(pass);

            let response = await fetch(url);

            let text = await response.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                console.error("JSON parse hatası:", err);
                return { status: "error", message: "Geçersiz JSON" };
            }
        }

        document.getElementById("loginForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            const username = e.target.username.value.trim();
            const pass = e.target.password.value;

            const msg = document.getElementById("message");

            let loginData = await login(username, pass);
            if (loginData.status !== "success") {
                msg.innerHTML = `<div class="bg-red-100 text-red-700 p-3 rounded mb-4">` + loginData.message + `</div>`;
                return;
            }

            const cookieModule = await cookiemanager;
            cookieModule.setCookie("capsule_user", loginData.user.apikey, 30);
            cookieModule.setCookie("capsule_username", loginData.user.username, 30);
            cookieModule.setCookie("capsule_logged", "true", 30);

            msg.innerHTML = `<div class="bg-green-100 text-green-700 p-3 rounded mb-4">Giriş başarılı!</div>`;

            // yönlendir
            setTimeout(() => {
                if (href) {
                    window.location.href = href;
                } else {
                    window.location.href = "http://capsule.net.tr/index.php";
                }
            }, 700);
        });
    </script>

</body>

</html>