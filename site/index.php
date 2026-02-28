<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Capsule</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .game-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .skeleton {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }
    </style>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center space-x-4">
                <img src="CapsuleLogo.png" alt="Capsule Logo" class="h-14">
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-6 w-full md:w-auto">
                <nav class="flex flex-wrap items-center gap-4">
                    <a href="index.php" class="text-gray-600 hover:text-indigo-600 font-medium">Ana Sayfa</a>
                    <a href="studio/" class="text-gray-600 hover:text-indigo-600 font-medium">Studio</a>
                    <a href="https://capsule." class="text-gray-600 hover:text-indigo-600 font-medium">Status</a>
                    <a href="https://discord.gg/jMmZZjQxk8" target="_blank"
                        class="text-gray-600 hover:text-indigo-600 font-medium">Discord</a>
                </nav>

                <div id="auth-buttons" class="flex items-center gap-2 md:ml-6 md:justify-end">
                    <a href="login/login.php"
                        class="px-4 py-2 text-sm font-semibold text-indigo-600 border border-indigo-600 rounded hover:bg-indigo-50 transition">Giriş
                        Yap</a>
                    <a href="login/register.php"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700 transition">Kayıt
                        Ol</a>
                </div>
            </div>
        </header>

        <!-- Yükleme durumu -->
        <div id="loading" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- Skeleton loader -->
            <div class="skeleton bg-gray-200 rounded-lg h-64"></div>
            <div class="skeleton bg-gray-200 rounded-lg h-64"></div>
            <div class="skeleton bg-gray-200 rounded-lg h-64"></div>
            <div class="skeleton bg-gray-200 rounded-lg h-64"></div>
            <div class="skeleton bg-gray-200 rounded-lg h-64"></div>
            <div class="skeleton bg-gray-200 rounded-lg h-64"></div>
            <div class="skeleton bg-gray-200 rounded-lg h-64"></div>
            <div class="skeleton bg-gray-200 rounded-lg h-64"></div>
        </div>

        <!-- Hata mesajı -->
        <div id="error" class="hidden bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"></div>

        <div id="warning" class="hidden bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded">
        </div>

        <!-- Oyun listesi -->
        <div id="games-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"></div>
    </div>

    <script>
        const API_URL = 'api/v1/games/';

        function updateAuthButtons() {
            const authButtons = document.getElementById('auth-buttons');
            if (!authButtons) return;

            const cookieString = document.cookie || '';
            const isLoggedIn = cookieString.includes('capsule_logged=true');
            const usernameMatch = cookieString.match(/(?:^|; )capsule_username=([^;]+)/);
            const username = usernameMatch ? decodeURIComponent(usernameMatch[1]) : '';

            if (isLoggedIn) {
                const profileLink = username ? `profile/?username=${encodeURIComponent(username)}` : 'studio/';
                authButtons.innerHTML = `
                    <a href="${profileLink}"
                        class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded hover:bg-green-700 transition">
                        ${username ? `${username} (Profil)` : 'Panele Git'}
                    </a>
                `;
            }
        }

        async function fetchGames() {
            try {
                const response = await fetch(API_URL, {
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.status !== 'success') {
                    throw new Error(data.message || 'Invalid response from server');
                }

                return data.data || [];

            } catch (error) {
                console.error('Fetch error:', error);
                throw error;
            }
        }

        async function fetchAnnouncement() {
            try {
                const response = await fetch('api/v1/maintenance/announcement.php', {
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                return data;
            } catch (error) {
                console.error('Fetch error:', error);
                throw error;
            }
        }

        function renderGames(games) {
            const container = document.getElementById('games-container');

            if (!games || games.length === 0) {
                container.innerHTML = `
                    <div class="col-span-full text-center py-10">
                        <p class="text-gray-500">Henüz oyun eklenmemiş</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = games.map(game => ` 
                <a href="${game.id ? `game.php?id=${game.id}` : '#'}" 
       class="game-card block bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition relative">
                    <img src="${game.image_url}" 
                        alt="${game.title}"
                        class="w-full h-48 object-cover"
                        loading="lazy"
                        onerror="this.src='https://placehold.co/600x400'">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-800 truncate">${game.title}</h3>
                        <p class="text-gray-600 text-sm mt-2 line-clamp-2">${game.description || 'Açıklama yok'}</p>
                        
                        <div class="flex justify-between items-center mt-3 text-sm text-gray-500">
                            <span class="flex items-center">👥 ${game.players || 0}</span>
                            <span class="flex items-center">❤️ ${game.likes || 0}</span>
                        </div>

                        <!-- Oyna tuşu -->
                        <button onclick="" class="mt-4 w-full text-center bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded transition">
                            Oyna
                        </button>
                    </div>
                </a>
            `).join('');
        }

        async function init() {
            try {
                const games = await fetchGames();
                renderGames(games);

                const announcement = await fetchAnnouncement();
                const warningElement = document.getElementById('warning');
                warningElement.textContent = announcement.announcement_message;
                if (announcement.announcement_mode == true) {
                    warningElement.classList.remove('hidden');
                }

            } catch (error) {
                const errorElement = document.getElementById('error');
                errorElement.textContent = `Oyunlar yüklenirken hata oluştu: ${error.message}`;
                errorElement.classList.remove('hidden');
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Uygulamayı başlat
        document.addEventListener('DOMContentLoaded', () => {
            updateAuthButtons();
            init();
        });
    </script>
</body>

</html>