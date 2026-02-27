<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avatar Ayarı</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Avatar Ayarı</h2>
            <form onsubmit="updateAvatar(event)">
                <div class="mb-4">
                    <label for="avatar" class="block text-gray-700">Avatar Rengi</label>
                    <input type="color" id="avatar" name="avatar" class="w-full p-2 border border-gray-300 rounded"
                        required />
                </div>
                <button type="submit"
                    class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">Güncelle</button>
            </form>
        </div>
    </div>
</body>
<script>
    const cookiemanager = import("../login/cookiemanager.js");

    async function isLogged() {
        const cookieModule = await cookiemanager;
        return cookieModule.isCookieValid("capsule_logged");
    }

    isLogged().then(function (value) {
        if (!value) {
            window.location.href = "http://capsule.net.tr/login/login.php?href=" + encodeURIComponent(window.location.href);
        }
    });

    async function updateAvatar(event) {
        event.preventDefault();
        let color = document.getElementById('avatar').value;
        const cookieModule = await cookiemanager;
        const apikey = cookieModule.getCookie("capsule_user");

        const jsonData = {
            "color": "" + (color),
            "faceURL": "http://capsule.net.tr/api/v1/avatar/avatarurl.php?type=face",
            "avatars": []
        }

        const response = await fetch("http://capsule.net.tr/api/v1/avatar/?type=set&apikey=" + apikey + "&data=" + encodeURIComponent(JSON.stringify(jsonData)), {
            method: "GET",
            headers: {
                "Content-Type": "application/json"
            }
        });

        const data = await response.json();
        if (data.status !== "success") throw new Error(data.message || "Avatar güncellenemedi.");

        alert("Avatar güncellendi!");
    }
</script>

</html>