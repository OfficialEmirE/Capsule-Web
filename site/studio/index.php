<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8" />
  <title>Capsule Studio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>

<body class="bg-gray-100">
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-md p-4 flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <img src="../CapsuleStudioLogo.png" alt="Capsule Logo" class="h-10 rounded-full" />
      </div>
      <nav class="space-x-4 text-sm text-gray-600">
        <a href="index.php" class="hover:text-indigo-600">Ana Sayfa</a>
        <a href="../index.php" class="hover:text-indigo-600 font-semibold">Capsule Dön</a>
        <a href="https://discord.gg/jMmZZjQxk8" class="hover:text-indigo-600">Discord</a>
      </nav>
    </header>

    <div class="container mx-auto px-4 py-8">
      <div id="error" class="hidden bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"></div>
      <div id="warning" class="hidden bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded"></div>
    </div>

    <!-- Main -->
    <main class="flex-1 container mx-auto px-4 py-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Oyunların</h2>
        <button onclick="openCreateGameModal()"
          class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Yeni Oyun Oluştur</button>
      </div>

      <!-- Modal -->
      <div id="createGameModal" class="fixed inset-0 bg-gray-500 bg-opacity-50 flex justify-center items-center hidden">
        <div class="bg-white p-6 rounded shadow-lg w-96">
          <form onsubmit="createGame(event)">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Yeni Oyun Oluştur</h3>
            <div class="mb-4">
              <label for="title" class="block text-gray-700">Oyun Adı</label>
              <input type="text" id="title" name="title" class="w-full p-2 border border-gray-300 rounded" required
                maxlength="100" />
            </div>
            <div class="mb-4">
              <label for="description" class="block text-gray-700">Açıklama</label>
              <textarea id="description" name="description" class="w-full p-2 border border-gray-300 rounded"
                maxlength="100"></textarea>
            </div>
            <div class="mb-4">
              <label for="image_url" class="block text-gray-700">Görsel URL</label>
              <input type="url" id="image_url" name="image_url" class="w-full p-2 border border-gray-300 rounded"
                placeholder="https://..." />
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">Oyun
              Oluştur</button>
            <button type="button" onclick="closeCreateGameModal()"
              class="mt-4 w-full bg-gray-300 text-gray-700 py-2 rounded">Kapat</button>
          </form>
        </div>
      </div>

      <!-- Oyunlar -->
      <div id="games-container" class="space-y-6"></div>
      <div id="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-indigo-500"></div>
        <p class="mt-2 text-gray-600">Oyunlar yükleniyor...</p>
      </div>
    </main>
  </div>

  <!-- JS -->
  <script>
    const cookiemanager = import("../login/cookiemanager.js");

    function openCreateGameModal() {
      document.getElementById('createGameModal').classList.remove('hidden');
    }
    function closeCreateGameModal() {
      document.getElementById('createGameModal').classList.add('hidden');
    }

    async function isLogged() {
      const cookieModule = await cookiemanager;
      let value = cookieModule.isCookieValid("capsule_logged");
      return value;
    }

    async function loadGames() {
      const container = document.getElementById('games-container');
      const loading = document.getElementById('loading');
      const username = (await cookiemanager).getCookie("capsule_username");

      try {
        const response = await fetch('../api/v1/games/?username=' + username);
        const data = await response.json();

        if (data.status !== 'success') throw new Error(data.message || 'Veri alınamadı.');
        loading.style.display = 'none';

        container.innerHTML = data.data.map(game => `
            <div class="bg-white rounded shadow flex overflow-hidden">
                <div style="width: 600px; height: 300px; flex-shrink: 0;">
                <img src="${game.image_url || 'https://placehold.co/800?text=[Error404]&font=roboto'}" alt="${game.title}" class="w-full h-full object-cover" />
                </div>
                <div class="flex-1 flex flex-col justify-between p-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">${game.title}</h3>
                    <p class="text-sm text-gray-600 mb-2">${game.description || 'Açıklama yok.'}</p>
                </div>
                <p class="text-xs text-gray-500 mt-2">AW</p>
                </div>
                <div class="flex flex-col justify-center items-end p-4 space-y-2">
                <a href="/game.php?id=${game.id}" class="px-6 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded text-sm font-medium">Oyna</a>
                <button onclick="deleteGame(${game.id})" class="px-6 py-2 bg-red-600 text-white hover:bg-red-700 rounded text-sm font-medium">Sil</button>
                <button onclick="editGame(${game.id})" class="px-6 py-2 border border-black text-black hover:bg-black hover:text-white rounded text-sm font-medium">Düzenle</button>
                </div>
            </div>
            `).join('');
      } catch (error) {
        if (error.message === `Game not found`) {
          loading.innerHTML = `Oyununuz Yok`;
        } else {
          loading.innerHTML = `<div class="bg-red-100 text-red-700 p-4 rounded"><strong>Hata:</strong> ${error.message}</div>`;
        }
      }
    }

    async function deleteGame(id) {
      if (!confirm('Bu oyunu silmek istediğinize emin misiniz?')) return;

      const cookieModule = await cookiemanager;
      const apikey = cookieModule.getCookie("capsule_user");

      try {
        const response = await fetch(`http://capsule.net.tr/api/v1/games/delete.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id: id,
            apikey: apikey
          })
        });

        const data = await response.json();
        if (data.status === 'success') {
          loadGames();
        } else {
          const errorElement = document.getElementById('error');
          errorElement.textContent = 'Silme işlemi başarısız: ' + data.message;
          errorElement.classList.remove('hidden');
        }
      } catch (error) {
        const errorElement = document.getElementById('error');
        errorElement.textContent = 'Bir hata oluştu: ' + error.message;
        errorElement.classList.remove('hidden');
      }
    }

    async function sendCreateGame(apiKey, gameName, desc, image_url) {
      const response = await fetch("http://capsule.net.tr/api/v1/games/create.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
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
          throw new Error(json.message || 'Server error: ' + response.status);
        } catch (e) {
          throw new Error('Server error (' + response.status + '): ' + text.substring(0, 100)); // Show beginning of error
        }
      }

      return await response.json();
    }

    async function createGame(event) {
      if (event) event.preventDefault();

      const cookieModule = await cookiemanager;
      const apiKey = cookieModule.getCookie("capsule_user");

      const gameName = document.getElementById("title").value;
      const description = document.getElementById("description").value;
      const image_url = document.getElementById("image_url").value;

      try {
        const data = await sendCreateGame(apiKey, gameName, description, image_url);
        console.log(data);

        if (data.status === 'success') {
          closeCreateGameModal();
          loadGames();
          if (event) event.target.reset();
        } else {
          alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
        }
      } catch (e) {
        console.error(e);
        alert('Bir hata oluştu: ' + e.message);
      }
    }

    function openCustomProtocol(url) {
      const iframe = document.createElement('iframe');
      iframe.style.display = 'none';
      iframe.src = url;
      document.body.appendChild(iframe);

      // Temizlik için kısa süre sonra kaldırabilirsin
      setTimeout(() => {
        document.body.removeChild(iframe);
      }, 100);
    }

    function editGame(id) {
      openCustomProtocol(`capsule://studio/${id}`);
    }

    isLogged().then(function (value) {
      if (!value) {
        window.location.href = "http://capsule.net.tr/login/login.php?href=" + encodeURIComponent(window.location.href);
      }
    });

    window.addEventListener('DOMContentLoaded', loadGames);
  </script>
</body>

</html>