<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>| Capsule</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!--<style>
        .profile-header-bg {
            background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
        }
    </style>-->
</head>

<body class="bg-gray-100 min-h-screen font-sans">
    <!-- Navbar -->
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="CapsuleLogo.png" alt="Capsule Logo" class="h-14">
            </div>
            <nav class="flex space-x-4">
                <a href="index.php" class="text-gray-600 hover:text-indigo-600 font-medium">Ana Sayfa</a>
                <a href="studio/" class="text-gray-600 hover:text-indigo-600 font-medium">Studio</a>
                <a href="status.php" class="text-gray-600 hover:text-indigo-600 font-medium">Status</a>

                <a href="https://discord.gg/sqFCrgeUJg" target="_blank"
                    class="text-gray-600 hover:text-indigo-600 font-medium">Discord</a>
            </nav>
        </header>

        <!-- Oyun içeriği -->
        <div id="game-container"></div>

        <div id="error" class="hidden bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"></div>
    </div>

    <main class="container mx-auto px-4 py-8">
        <!-- Error Container -->
        <div id="error-container"
            class="hidden max-w-4xl mx-auto bg-red-50 text-red-700 p-4 rounded-lg border border-red-200 mb-6"></div>

        <!-- Loading State -->
        <div id="loading" class="text-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
            <p class="mt-4 text-gray-500">Profil yükleniyor...</p>
        </div>

        <!-- Profile Content -->
        <div id="profile-content" class="hidden">
            <!-- Header Section -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                <!-- Cover Photo (Simulated) -->
                <div class="h-48 profile-header-bg w-full relative"></div>

                <div class="px-6 pb-6 relative">
                    <div class="flex flex-col md:flex-row items-center md:items-end -mt-16 md:-mt-20">
                        <!-- Avatar -->
                        <div
                            class="relative z-10 w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-white bg-gray-200 overflow-hidden shadow-md">
                            <img id="profile-avatar" src="" alt="Avatar" class="w-full h-full object-cover">
                        </div>

                        <!-- User Info -->
                        <div class="mt-4 md:mt-0 md:ml-6 flex-1 text-center md:text-left mb-2">
                            <h1 id="profile-username" class="text-3xl font-bold text-gray-900"></h1>
                            <p id="profile-bio" class="text-gray-500 mt-1">Capsule Geliştiricisi</p>
                        </div>

                        <!-- Actions (Friend, Message, etc.) -->
                        <div class="mt-4 md:mt-0 md:mb-4 flex space-x-3">
                            <button
                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded-lg transition duration-200">
                                Mesaj Gönder
                            </button>
                            <button
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition duration-200">
                                Arkadaş Ekle
                            </button>
                        </div>
                    </div>

                    <!-- Divider -->
                    <hr class="mt-6 border-gray-100">

                    <!-- About / Bio -->
                    <div class="mt-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Hakkında</h2>
                        <p class="text-gray-600 leading-relaxed text-sm">
                            Bu kullanıcı henüz bir açıklama girmedi.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-6 space-x-8">
                <button
                    class="py-4 px-1 border-b-2 border-indigo-500 text-indigo-600 font-bold text-sm">Oluşturdukları</button>
                <button
                    class="py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm transition">Rozetler</button>
                <button
                    class="py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm transition">Gruplar</button>
            </div>

            <!-- Games Grid -->
            <div>
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Oyunlar
                </h2>

                <div id="games-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <!-- Games will be injected here -->
                </div>

                <div id="no-games"
                    class="hidden text-center py-12 bg-white rounded-lg border border-dashed border-gray-300">
                    <p class="text-gray-500">Bu kullanıcının henüz aktif bir oyunu yok.</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        const params = new URLSearchParams(window.location.search);
        const username = params.get('username');

        const avatarUrl = `../api/v1/avatar/?type=get&username=${encodeURIComponent(username || '')}`;
        // Note: Using the games API which supports username filtering
        const gamesUrl = `../api/v1/games/?username=${encodeURIComponent(username || '')}`;

        const dom = {
            loading: document.getElementById('loading'),
            content: document.getElementById('profile-content'),
            error: document.getElementById('error-container'),
            avatar: document.getElementById('profile-avatar'),
            username: document.getElementById('profile-username'),
            gamesGrid: document.getElementById('games-grid'),
            noGames: document.getElementById('no-games')
        };

        function showError(msg) {
            dom.loading.classList.add('hidden');
            dom.content.classList.add('hidden');
            dom.error.textContent = msg;
            dom.error.classList.remove('hidden');
        }

        async function initProfile() {
            if (!username) {
                showError("Kullanıcı adı belirtilmedi. (?username=...)");
                return;
            }

            try {
                // 1. Fetch Basic Info & Avatar Check
                // We'll fetch the avatar endpoint to verify user existence primarily
                const avRes = await fetch(avatarUrl);
                const avJson = await avRes.json();

                if (avJson.status === 'error' && avJson.message === 'User not found') {
                    showError("Kullanıcı bulunamadı.");
                    return;
                }

                // If avatar API fails generically but not 404, we might still proceed, 
                // but let's assume it works for user validation.
                if (avJson.status === 'success') {
                    dom.avatar.src = avJson.avatar || '../WebB.png'; // Fallback
                } else {
                    // Fallback image if error other than not found
                    dom.avatar.src = '../WebB.png';
                }

                dom.username.textContent = username;

                // 2. Fetch Games
                const gamesRes = await fetch(gamesUrl);
                const gamesJson = await gamesRes.json();

                dom.loading.classList.add('hidden');
                dom.content.classList.remove('hidden');

                if (gamesJson.status === 'success' && Array.isArray(gamesJson.data) && gamesJson.data.length > 0) {
                    renderGames(gamesJson.data);
                } else {
                    dom.noGames.classList.remove('hidden');
                }

            } catch (e) {
                console.error(e);
                showError("Profil yüklenirken bir sorun oluştu.");
            }
        }

        function renderGames(games) {
            dom.gamesGrid.innerHTML = games.map(game => `
                <a href="../game.php?id=${game.id}" class="group bg-white rounded-lg shadow-sm hover:shadow-md transition duration-200 overflow-hidden border border-gray-100 block">
                    <div class="aspect-w-16 aspect-h-9 bg-gray-200 relative overflow-hidden">
                        <img src="${game.image_url}" class="object-cover w-full h-40 group-hover:scale-105 transition duration-500 ease-out" 
                             onerror="this.src='https://placehold.co/400x225?text=No+Image'">
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition"></div>
                    </div>
                    <div class="p-3">
                        <h3 class="font-bold text-gray-900 truncate" title="${escapeHtml(game.title)}">${escapeHtml(game.title)}</h3>
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                            <span class="flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                ${formatNumber(game.visits || 0)}
                            </span>
                            <span>${game.players ? '🟢 ' + game.players : '⚪ 0'}</span>
                        </div>
                    </div>
                </a>
            `).join('');
        }

        function escapeHtml(text) {
            if (!text) return "";
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('en-US', { notation: "compact", maximumFractionDigits: 1 }).format(num);
        }

        document.addEventListener('DOMContentLoaded', initProfile);
    </script>
</body>

</html>