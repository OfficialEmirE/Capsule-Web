<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Hesap Ayarlari | Capsule</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <style>
        .settings-card {
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center space-x-4">
                <img src="../CapsuleLogo.png" alt="Capsule Logo" class="h-14">
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-6 w-full md:w-auto">
                <nav class="flex flex-wrap items-center gap-4">
                    <a href="../index.php" class="text-gray-600 hover:text-indigo-600 font-medium">Ana Sayfa</a>
                    <a href="../studio/" class="text-gray-600 hover:text-indigo-600 font-medium">Studio</a>
                    <a href="./" class="text-indigo-600 font-semibold">Hesap Ayarlari</a>
                    <a href="https://capsule.net.tr" class="text-gray-600 hover:text-indigo-600 font-medium">Status</a>
                    <a href="https://discord.gg/jMmZZjQxk8" target="_blank"
                        class="text-gray-600 hover:text-indigo-600 font-medium">Discord</a>
                </nav>

                <div id="auth-buttons" class="flex items-center gap-2 md:ml-6 md:justify-end">
                    <a href="../login/login.php"
                        class="px-4 py-2 text-sm font-semibold text-indigo-600 border border-indigo-600 rounded hover:bg-indigo-50 transition">Giris
                        Yap</a>
                    <a href="../login/register.php"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700 transition">Kayit
                        Ol</a>
                </div>
            </div>
        </header>

        <div id="status-message" class="hidden p-4 mb-6 rounded border-l-4"></div>

        <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="settings-card bg-white rounded-lg p-6 shadow-sm lg:col-span-1">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Hesap Ozeti</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-500">Kullanici Adi</p>
                        <p id="account-username" class="text-gray-900 font-semibold">-</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Kayit Tarihi</p>
                        <p id="account-created-at" class="text-gray-900">Yukleniyor...</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Profil</p>
                        <a id="profile-link" href="../profile/"
                            class="inline-block mt-1 text-indigo-600 hover:text-indigo-700 font-medium">Profili Gör</a>
                    </div>
                </div>
            </section>

            <section class="settings-card bg-white rounded-lg p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Avatar Ayarı</h2>
                <p class="text-sm text-gray-500 mb-5">Renk değerini guncelleyebilirsin.</p>

                <div class="flex flex-col md:flex-row gap-6">
                    <div class="md:w-48 flex-shrink-0">
                        <div id="avatar-preview"
                            class="w-40 h-40 rounded-full border-4 border-white shadow mx-auto bg-gray-200 overflow-hidden relative">
                            <img id="avatar-face-preview" src="" alt="Avatar"
                                class="hidden w-full h-full object-cover absolute inset-0">
                        </div>
                    </div>

                    <form id="avatar-form" class="flex-1 space-y-4">
                        <div>
                            <label for="avatar-color" class="block text-sm font-medium text-gray-700 mb-1">Avatar
                                Rengi</label>
                            <input id="avatar-color" type="color"
                                class="h-10 w-24 p-1 border border-gray-300 rounded cursor-pointer">
                        </div>

                        <button id="avatar-save-button" type="submit"
                            class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700 transition">
                            Avatari Kaydet
                        </button>
                    </form>
                </div>
            </section>

            <section class="settings-card bg-white rounded-lg p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Kullanici Adi Degistirme</h2>
                <p class="text-sm text-gray-500 mb-4">Bu alan UI olarak hazirlandi. API baglantisi sonraki adimda
                    aktif edilebilir.</p>
                <form id="username-form" class="flex flex-col md:flex-row gap-3">
                    <input id="new-username" type="text" maxlength="24" placeholder="Yeni kullanici adi"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-gray-700 rounded hover:bg-gray-800 transition">
                        Guncelle (Yakinda)
                    </button>
                </form>
            </section>

            <section class="settings-card bg-white rounded-lg p-6 shadow-sm lg:col-span-1 border border-red-100">
                <h2 class="text-lg font-semibold text-red-700 mb-1">Tehlikeli Islem</h2>
                <p class="text-sm text-gray-600 mb-4">Hesap silme aksiyonu su an pasif. Istersen API ile
                    etkinlestirebiliriz.</p>
                <button id="delete-account-button" type="button"
                    class="w-full px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded hover:bg-red-700 transition">
                    Hesabi Sil (Yakinda)
                </button>
            </section>
        </main>
    </div>

    <script>
        const cookiemanager = import('../login/cookiemanager.js');

        const dom = {
            status: document.getElementById('status-message'),
            authButtons: document.getElementById('auth-buttons'),
            username: document.getElementById('account-username'),
            createdAt: document.getElementById('account-created-at'),
            profileLink: document.getElementById('profile-link'),
            preview: document.getElementById('avatar-preview'),
            previewFace: document.getElementById('avatar-face-preview'),
            avatarForm: document.getElementById('avatar-form'),
            avatarColor: document.getElementById('avatar-color'),
            avatarSaveButton: document.getElementById('avatar-save-button'),
            usernameForm: document.getElementById('username-form'),
            deleteAccountButton: document.getElementById('delete-account-button')
        };

        const state = {
            username: '',
            apikey: '',
            avatarData: {
                color: '#e5e7eb',
                faceURL: '',
                avatars: []
            }
        };

        function clearAuthCookies() {
            const cookieNames = ['capsule_user', 'capsule_username', 'capsule_logged'];
            cookieNames.forEach((cookieName) => {
                document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
            });
        }

        function showStatus(type, text) {
            const classMap = {
                success: 'bg-green-50 border-green-500 text-green-700',
                error: 'bg-red-50 border-red-500 text-red-700',
                info: 'bg-blue-50 border-blue-500 text-blue-700'
            };
            const classes = classMap[type] || classMap.info;
            dom.status.className = `p-4 mb-6 rounded border-l-4 ${classes}`;
            dom.status.textContent = text;
            dom.status.classList.remove('hidden');
        }

        function normalizeColor(color) {
            if (typeof color !== 'string') return '#e5e7eb';
            const trimmed = color.trim();
            if (/^#[0-9A-Fa-f]{6}$/.test(trimmed)) return trimmed;
            return '#e5e7eb';
        }

        function parseAvatarData(rawAvatar) {
            if (!rawAvatar) {
                return { color: '#e5e7eb', faceURL: '', avatars: [] };
            }

            if (typeof rawAvatar === 'object') {
                return {
                    color: normalizeColor(rawAvatar.color),
                    faceURL: rawAvatar.faceURL || '',
                    avatars: Array.isArray(rawAvatar.avatars) ? rawAvatar.avatars : []
                };
            }

            try {
                const parsed = JSON.parse(rawAvatar);
                return {
                    color: normalizeColor(parsed.color),
                    faceURL: parsed.faceURL || '',
                    avatars: Array.isArray(parsed.avatars) ? parsed.avatars : []
                };
            } catch (error) {
                return { color: '#e5e7eb', faceURL: '', avatars: [] };
            }
        }

        function renderAvatarPreview() {
            const color = normalizeColor(dom.avatarColor.value);
            const faceApiURL = "https://ramazanenescik04.github.io/capsule-character-generator/generate-face.js";

            import(faceApiURL).then((module) => {
                const faceURL = module.generateFace(color);
                updatePreview(faceURL);
            }).catch(() => {
                updatePreview('');
            });

            if (faceURL) {
                dom.previewFace.src = faceURL;
                dom.previewFace.classList.remove('hidden');
            } else {
                dom.previewFace.src = '';
                dom.previewFace.classList.add('hidden');
            }
        }

        function updatePreview(faceURL) {
            dom.preview.style.backgroundColor = normalizeColor(dom.avatarColor.value);
            if (faceURL) {
                dom.previewFace.src = faceURL;
                dom.previewFace.classList.remove('hidden');
            } else {
                dom.previewFace.src = '';
                dom.previewFace.classList.add('hidden');
            }
        }   

        function updateAuthButtons() {
            if (!dom.authButtons) return;

            const cookieString = document.cookie || '';
            const isLoggedIn = cookieString.includes('capsule_logged=true');
            const usernameMatch = cookieString.match(/(?:^|; )capsule_username=([^;]+)/);
            const username = usernameMatch ? decodeURIComponent(usernameMatch[1]) : '';

            if (isLoggedIn) {
                const profileLink = username ? `../profile/?username=${encodeURIComponent(username)}` : '../studio/';
                dom.authButtons.innerHTML = `
                    <div id="account-menu" class="relative">
                        <button id="account-menu-button" type="button"
                            class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded hover:bg-green-700 transition focus:outline-none">
                            ${username ? username : 'Hesabim'}
                        </button>
                        <div id="account-menu-dropdown"
                            class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden z-50">
                            <a href="./" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                                Hesap Ayarlari
                            </a>
                            <a href="${profileLink}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                                Profil
                            </a>
                            <button id="logout-button" type="button"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                                Oturumdan Cik
                            </button>
                        </div>
                    </div>
                `;

                const accountMenu = document.getElementById('account-menu');
                const accountMenuButton = document.getElementById('account-menu-button');
                const accountMenuDropdown = document.getElementById('account-menu-dropdown');
                const logoutButton = document.getElementById('logout-button');

                if (!accountMenu || !accountMenuButton || !accountMenuDropdown || !logoutButton) return;

                const closeMenu = () => accountMenuDropdown.classList.add('hidden');

                accountMenuButton.addEventListener('click', (event) => {
                    event.stopPropagation();
                    accountMenuDropdown.classList.toggle('hidden');
                });

                document.addEventListener('click', (event) => {
                    if (!accountMenu.contains(event.target)) closeMenu();
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') closeMenu();
                });

                logoutButton.addEventListener('click', () => {
                    clearAuthCookies();
                    window.location.href = '../index.php';
                });
            }
        }

        async function ensureAuth() {
            const cookieModule = await cookiemanager;
            const loggedIn = cookieModule.isCookieValid('capsule_logged');
            if (!loggedIn) {
                window.location.href = `../login/login.php?href=${encodeURIComponent(window.location.href)}`;
                return false;
            }

            state.username = cookieModule.getCookie('capsule_username');
            state.apikey = cookieModule.getCookie('capsule_user');

            if (!state.username || !state.apikey) {
                showStatus('error', 'Hesap bilgisi eksik. Lutfen tekrar giris yap.');
                return false;
            }

            return true;
        }

        async function loadAccountSummary() {
            dom.username.textContent = state.username;
            dom.profileLink.href = `../profile/?username=${encodeURIComponent(state.username)}`;

            try {
                const response = await fetch('../api/v1/account/');
                const data = await response.json();

                if (data.status === 'success' && Array.isArray(data.data)) {
                    const user = data.data.find((item) => item.username === state.username);
                    if (user && user.creation_date) {
                        dom.createdAt.textContent = user.creation_date;
                        return;
                    }
                }

                dom.createdAt.textContent = 'Bilinmiyor';
            } catch (error) {
                dom.createdAt.textContent = 'Bilinmiyor';
            }
        }

        async function loadAvatarSettings() {
            try {
                const url = `../api/v1/avatar/?type=get&username=${encodeURIComponent(state.username)}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.status !== 'success') {
                    throw new Error(data.message || 'Avatar bilgisi alinamadi');
                }

                state.avatarData = parseAvatarData(data.avatar);
            } catch (error) {
                state.avatarData = { color: '#e5e7eb', faceURL: '', avatars: [] };
                showStatus('info', 'Avatar bilgisi alinmadi. Varsayilan degerler yuklendi.');
            }

            dom.avatarColor.value = state.avatarData.color;
            renderAvatarPreview();
        }

        function getAvatarPayloadFromForm() {
            const avatars = dom.avatarLayers.value
                .split('\n')
                .map((line) => line.trim())
                .filter(Boolean);

            return {
                color: normalizeColor(dom.avatarColor.value),
                faceURL: dom.avatarFaceUrl.value.trim(),
                avatars
            };
        }

        async function saveAvatar(event) {
            event.preventDefault();

            const payload = getAvatarPayloadFromForm();
            const requestUrl = `../api/v1/avatar/?type=set&apikey=${encodeURIComponent(state.apikey)}&data=${encodeURIComponent(JSON.stringify(payload))}`;

            dom.avatarSaveButton.disabled = true;
            dom.avatarSaveButton.classList.add('opacity-70', 'cursor-not-allowed');

            try {
                const response = await fetch(requestUrl);
                const result = await response.json();

                if (result.status !== 'success') {
                    throw new Error(result.message || 'Avatar guncellenemedi.');
                }

                state.avatarData = payload;
                renderAvatarPreview();
                showStatus('success', 'Avatar basariyla guncellendi.');
            } catch (error) {
                showStatus('error', `Avatar guncellenemedi: ${error.message}`);
            } finally {
                dom.avatarSaveButton.disabled = false;
                dom.avatarSaveButton.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }

        function bindStaticActions() {
            dom.avatarColor.addEventListener('input', renderAvatarPreview);
            dom.avatarForm.addEventListener('submit', saveAvatar);

            dom.usernameForm.addEventListener('submit', (event) => {
                event.preventDefault();
                showStatus('info', 'Kullanici adi degistirme UI hazir. API endpoint eklendiginde aktif hale gelir.');
            });

            dom.deleteAccountButton.addEventListener('click', () => {
                showStatus('info', 'Hesap silme islemi guvenlik icin su an pasif.');
            });
        }

        async function initPage() {
            import('../login/cookiemanager.js').then((module) => {
                if (!module.isCookieValid('capsule_logged')) {
                    window.location.href = `../login/login.php?href=${encodeURIComponent(window.location.href)}`;
                }
            });

            updateAuthButtons();
            bindStaticActions();

            const isAuthenticated = await ensureAuth();
            if (!isAuthenticated) return;

            await Promise.all([loadAccountSummary(), loadAvatarSettings()]);
        }

        document.addEventListener('DOMContentLoaded', initPage);
    </script>
</body>

</html>
