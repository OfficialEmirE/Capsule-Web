<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>| Capsule</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="https://capsule.net.tr/">
                    <img src="CapsuleLogo.png" alt="Capsule Logo" class="h-14">
                </a>
            </div>
            <nav class="flex space-x-4">
                <a href="index.php" class="text-gray-600 hover:text-indigo-600 font-medium">Ana Sayfa</a>
                <a href="studio/" class="text-gray-600 hover:text-indigo-600 font-medium">Studio</a>
                <a href="https://capsule.instatus.com/"
                    class="text-gray-600 hover:text-indigo-600 font-medium">Status</a>

                <a href="https://discord.gg/sqFCrgeUJg" target="_blank"
                    class="text-gray-600 hover:text-indigo-600 font-medium">Discord</a>
            </nav>
        </header>

        <!-- Oyun içeriği -->
        <div id="game-container"></div>

        <div id="error" class="hidden bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"></div>
    </div>

    <script>
        const params = new URLSearchParams(window.location.search);
        const gameId = params.get('id') || 0;
        const API_URL = `api/v1/games/?id=${gameId}`;

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

        async function loadGame() {
            try {
                const res = await fetch(API_URL);
                const json = await res.json();

                if (json.status !== 'success') {
                    throw new Error(json.message || 'Oyun yüklenemedi');
                }

                const g = json.data;

                document.title = `${g.title} | Capsule`;

                document.getElementById('game-container').innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="md:col-span-2">
                <img src="${g.image_url}" class="w-full h-80 object-cover" 
                    onerror="this.src='https://placehold.co/800?text=[Error404]&font=roboto'">
            </div>
            <div class="p-6 flex flex-col justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">${g.title}</h1>
                    <a href="../profile?username=${g.username}">By ${g.username}</a>
                </div>
                <a href="javascript:openCustomProtocol('capsule://open/${g.id}')"
                        class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-lg mt-6 flex items-center justify-center">
                            Oyna
                    </a>
            </div>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
            <h2 class="font-semibold mb-3 text-lg">Oyun Açıklaması</h2>
            <p class="text-gray-700">${g.description || 'Açıklama yok'}</p>
        </div>

        <div class="flex justify-between items-center bg-gray-50 border rounded p-4 mt-6 text-sm text-gray-600">
            <div class="flex flex-wrap gap-6">
                <span>🟢 Aktif: ${g.players || 0}</span>
                <span>👀 Ziyaret: ${g.visits || 0}</span>
                <span>📅 Oluşturma: ${g.created_at || '-'}</span>
                <span>✏️ Güncelleme: ${g.updated_at || '-'}</span>
            </div>
            <a href="report.php?id=${g.id}" class="text-red-500 hover:underline">Oyunu Raporla</a>
        </div>
                `;

            } catch (e) {
                const err = document.getElementById('error');
                err.textContent = e.message;
                err.classList.remove('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', loadGame);
    </script>
</body>

</html>