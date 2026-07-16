<?php
// 1. Çıktı tamponlamayı başlat
ob_start();

// Eğer oturum başlatılmadıysa başlatıyoruz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Eğer projede tanımlı değilse ROOT_PATH ayarla
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

$userId = $id ?? $_GET['id'] ?? null;

// Oturum açmış aktif kullanıcının ID'sini alıyoruz
$loggedInUserId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;

// Hata durumunu en başta kontrol et
if (!$userId || !is_numeric($userId)) {
    if (file_exists(ROOT_PATH . 'error.php')) {
        http_response_code(404); // Hata kodunu set et
        $errorCode = 404;        // error.php'nin kullanması için değişken tanımla
        require ROOT_PATH . 'error.php';
    } else {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'No such file or directory'
        ], JSON_UNESCAPED_UNICODE);
    }
    ob_end_flush(); // Tamponu boşalt ve akışı durdur
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loading Profile... - Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/meta.php'; ?>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">

        <!-- PROFIL SAYFASINA ÖZEL STYLES -->
        <style>
            .profile-container {
                width: 970px;
                margin: 20px auto;
                display: grid;
                grid-template-columns: 300px 650px;
                gap: 20px;
            }

            /* Sol Sütun (Profil Kartı ve İstatistikler) */
            .profile-sidebar {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .profile-card {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                border-radius: 6px;
                padding: 15px;
                text-align: center;
                position: relative; /* Durum göstergesinin hizalanabilmesi için */
            }

            /* Doğrudan HTML'e gömülecek SVG'nin Alanı */
            .avatar-svg-container {
                width: 120px;
                height: 120px;
                margin: 0 auto 15px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .avatar-svg-container svg {
                width: 100%;
                height: 100%;
                display: block;
            }

            .profile-status-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                border: 2px solid #fff;
                position: absolute;
                top: 15px;
                right: 15px;
                background: #7f8c8d; /* Varsayılan Çevrimdışı */
                transition: background 0.3s ease;
                z-index: 3;
            }

            .profile-status-indicator.online {
                background: #2ecc71;
            }

            .profile-username {
                font-size: 20px;
                font-weight: bold;
                color: var(--text);
                margin-bottom: 5px;
                word-break: break-all;
            }

            .profile-bio-short {
                font-size: 12px;
                color: var(--muted);
                font-style: italic;
                margin-bottom: 12px;
            }

            .profile-statistics {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                border-radius: 6px;
                padding: 12px;
            }

            .profile-statistics h3 {
                font-size: 13px;
                border-bottom: 1px solid var(--panel-border);
                padding-bottom: 5px;
                margin-bottom: 10px;
            }

            .stat-row {
                display: flex;
                justify-content: space-between;
                font-size: 12px;
                margin-bottom: 6px;
            }

            .stat-row:last-child {
                margin-bottom: 0;
            }

            /* Sağ Sütun (Hakkında) */
            .profile-main-content {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .profile-panel {
                background: var(--panel);
                border: 1px solid var(--panel-border);
                border-radius: 6px;
                padding: 15px;
                position: relative;
            }

            .profile-panel h2 {
                font-size: 16px;
                font-weight: bold;
                border-bottom: 1px solid var(--panel-border);
                padding-bottom: 8px;
                margin-bottom: 12px;
                color: var(--text);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .profile-description {
                font-size: 13px;
                line-height: 1.5;
                color: #444;
                white-space: pre-wrap;
            }

            /* Otomatik linkler için ekstra stil */
            .profile-description a {
                color: #3498db;
                text-decoration: none;
            }

            .profile-description a:hover {
                text-decoration: underline;
            }

            .loading-skeleton {
                color: #888;
                font-style: italic;
                text-align: center;
                padding: 20px;
            }

            /* Düzenleme Alanı Tasarımları */
            .edit-bio-btn {
                background: #3498db;
                color: #fff;
                border: none;
                padding: 5px 10px;
                font-size: 11px;
                border-radius: 3px;
                cursor: pointer;
                font-weight: normal;
                display: none; /* JS ile sahibiyse gösterilecek */
                transition: background 0.2s;
            }

            .edit-bio-btn:hover {
                background: #2980b9;
            }

            .bio-edit-textarea {
                width: 100%;
                min-height: 120px;
                padding: 8px;
                border: 1px solid var(--panel-border);
                border-radius: 4px;
                font-family: inherit;
                font-size: 13px;
                resize: vertical;
                box-sizing: border-box;
                margin-bottom: 8px;
            }

            .bio-action-container {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }

            .bio-save-btn {
                background: #2ecc71;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
            }

            .bio-save-btn:hover {
                background: #27ae60;
            }

            .bio-cancel-btn {
                background: #e74c3c;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
            }

            .bio-cancel-btn:hover {
                background: #c0392b;
            }
        </style>
    </head>
    <body>
        <?php include ROOT_PATH . 'includes/header.php'; ?>

        <div class="profile-container">
            
            <!-- Sol Sütun -->
            <div class="profile-sidebar">
                
                <!-- Profil Kartı -->
                <div class="profile-card">
                    <!-- SVG nesnesini JS ile dolduracağımız boş kapsayıcı -->
                    <div id="avatarContainer" class="avatar-svg-container"></div>
                    <div id="statusIndicator" class="profile-status-indicator" title="Offline"></div>
                    
                    <div class="profile-username" id="profileUsername">Loading...</div>
                    <div class="profile-bio-short">User ID: #<?php echo (int)$userId; ?></div>
                </div>

                <!-- İstatistikler -->
                <div class="profile-statistics">
                    <h3>Statistics</h3> 
                    <div class="stat-row">
                        <span>Join Date:</span> 
                        <strong id="statJoinDate">-</strong> 
                    </div>
                </div>

            </div>

            <!-- Sağ Sütun -->
            <div class="profile-main-content">
                
                <!-- Hakkında (Biography) -->
                <div class="profile-panel">
                    <h2>
                        <span id="aboutTitle">About User</span>
                        <!-- Yazı yerine sadece kalem SVG ikonu içeren buton -->
                        <button id="editBioBtn" class="edit-bio-btn" title="Edit Biography">
                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                    </h2>
                    
                    <!-- Biyografi Görüntüleme Alanı -->
                    <div class="profile-description" id="profileBio">
                        <div class="loading-skeleton">Loading bio...</div>
                    </div>

                    <!-- Biyografi Düzenleme Form Alanı (Gizli Başlar) -->
                    <div id="bioEditContainer" style="display: none;">
                        <textarea id="bioTextarea" class="bio-edit-textarea" placeholder="Write something about yourself..."></textarea>
                        <div class="bio-action-container">
                            <button id="cancelBioBtn" class="bio-cancel-btn">Cancel</button>
                            <button id="saveBioBtn" class="bio-save-btn">Save Changes</button>
                        </div>
                    </div>
                </div>

            </div>
            
        </div>

        <?php include ROOT_PATH . 'includes/bottom.php'; ?>

        <!-- TAMAMEN JAVASCRIPT ODAKLI API ENTEGRASYONU -->
        <script>
        (function() {
            var userId = <?php echo json_encode((string)$userId); ?>;
            var loggedInUserId = <?php echo json_encode((string)$loggedInUserId); ?>;
            var originalBio = ""; // Değişiklik iptali için orijinal veriyi burada tutuyoruz

            // 1. KULLANICI BİLGİLERİNİ ÇEKELİM
            fetch('/api/v1/users/info?id=' + encodeURIComponent(userId))
                .then(function(res) {
                    if (!res.ok) throw new Error("User not found");
                    return res.json();
                })
                .then(function(data) {
                    if (data && data.status === 'success' && data.user) {
                        var user = data.user;
                        
                        // Sayfa Başlığını Güncelle
                        document.title = escapeHtml(user.username) + "'s Profile - Capsule Beta";
                        document.getElementById('profileUsername').textContent = user.username;
                        document.getElementById('aboutTitle').textContent = "About " + user.username;
                        
                        // Biyografi Ayarla
                        originalBio = user.bio ? user.bio : "";
                        updateBioUI(originalBio);

                        // Eğer oturum açmış aktif kullanıcı bu profilin sahibiyse düzenleme butonunu aç
                        if (loggedInUserId && String(loggedInUserId) === String(user.id)) {
                            document.getElementById('editBioBtn').style.display = 'inline-block';
                        }

                        // Kayıt Tarihi Formatla (YYYY-MM-DD -> Jan 12, 2013)
                        if (user.created_at) {
                            var date = new Date(user.created_at);
                            var options = { year: 'numeric', month: 'short', day: 'numeric' };
                            document.getElementById('statJoinDate').textContent = date.toLocaleDateString('en-US', options);
                        }

                        // 2. ARKA PLANDA SVG DOSYASINI ALALIM, BOYAYALIM VE HTML'E YAZALIM
                        var targetColor = user.avatar ? user.avatar : '#ffffff';
                        
                        fetch('/assets/images/body.svg')
                            .then(function(svgRes) {
                                return svgRes.text();
                            })
                            .then(function(svgText) {
                                // Gelen SVG düz metnini (DOM) olarak parse edelim
                                var parser = new DOMParser();
                                var svgDoc = parser.parseFromString(svgText, "image/svg+xml");
                                var svgElement = svgDoc.querySelector('svg');

                                if (svgElement) {
                                    // SVG içindeki tüm PATH elemanlarını ayıklayıp sadece beyaz olanları boyayalım
                                    var paths = svgElement.querySelectorAll('path');
                                    paths.forEach(function(path) {
                                        var fill = (path.getAttribute('fill') || '').toLowerCase();
                                        var stroke = (path.getAttribute('stroke') || '').toLowerCase();

                                        if (
                                            fill === '#ffffff' ||
                                            fill === '#fff' ||
                                            stroke === '#ffffff' ||
                                            stroke === '#fff'
                                        ) {
                                            path.setAttribute('fill', targetColor);
                                            path.setAttribute('stroke', targetColor);
                                        }
                                    });

                                    // Boyanmış SVG'yi doğrudan HTML içine basıyoruz! 
                                    var container = document.getElementById('avatarContainer');
                                    if (container) {
                                        container.innerHTML = '';
                                        container.appendChild(svgElement);
                                    }
                                }
                            })
                            .catch(function(svgErr) {
                                console.error("SVG fetch/parse hatası: ", svgErr);
                            });

                        // Çevrimiçi / Çevrimdışı Durumu
                        var statusIndicator = document.getElementById('statusIndicator');
                        if (statusIndicator) {
                            if (user.is_online) {
                                statusIndicator.classList.add('online');
                                statusIndicator.title = "Online";
                            } else {
                                statusIndicator.classList.remove('online');
                                statusIndicator.title = "Offline";
                            }
                        }

                    } else {
                        showErrorPage();
                    }
                })
                .catch(function(err) {
                    console.error(err);
                    showErrorPage();
                });

            // --- BİYOGRAFİ DÜZENLEME INTERAKTİF İŞLEMLERİ ---
            var editBtn = document.getElementById('editBioBtn');
            var cancelBtn = document.getElementById('cancelBioBtn');
            var saveBtn = document.getElementById('saveBioBtn');
            var bioView = document.getElementById('profileBio');
            var editContainer = document.getElementById('bioEditContainer');
            var textarea = document.getElementById('bioTextarea');

            // Düzenleme modunu aç
            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    bioView.style.display = 'none';
                    editBtn.style.display = 'none';
                    editContainer.style.display = 'block';
                    textarea.value = originalBio;
                    textarea.focus();
                });
            }

            // İptal Et
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    editContainer.style.display = 'none';
                    bioView.style.display = 'block';
                    editBtn.style.display = 'inline-block';
                });
            }

            // Verileri Kaydet ve API'ye POST et
            if (saveBtn) {
                saveBtn.addEventListener('click', function() {
                    var updatedText = textarea.value;
                    
                    saveBtn.disabled = true;
                    saveBtn.textContent = "Saving...";

                    fetch('/api/v1/users/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            bio: updatedText
                        })
                    })
                    .then(function(res) {
                        return res.json().then(function(data) {
                            if (!res.ok) throw new Error(data.message || "An error occurred during save.");
                            return data;
                        });
                    })
                    .then(function(data) {
                        if (data.status === 'success') {
                            originalBio = updatedText;
                            updateBioUI(originalBio);
                            
                            // Arayüzü eski haline getir
                            editContainer.style.display = 'none';
                            bioView.style.display = 'block';
                            editBtn.style.display = 'inline-block';
                        } else {
                            alert("Failed to save: " + (data.message || "Unknown error"));
                        }
                    })
                    .catch(function(err) {
                        alert("Error: " + err.message);
                    })
                    .finally(function() {
                        saveBtn.disabled = false;
                        saveBtn.textContent = "Save Changes";
                    });
                });
            }

            // Biyografi UI güncelleyici ve HTTP linkleri algılayıp otomatik a etiketi yapan fonksiyon
            function updateBioUI(bioContent) {
                var container = document.getElementById('profileBio');
                if (!container) return;

                if (bioContent && bioContent.trim() !== "") {
                    // Güvenlik için önce XSS korumasını çalıştırıyoruz
                    var escaped = escapeHtml(bioContent);

                    // HTTP ve HTTPS linklerini tespit eden regex deseni
                    var linkRegex = /(https?:\/\/[^\s]+)/g;

                    // Yakalanan linkleri tıklanabilir hale getir (Target blank & rel noopener güvenlik için)
                    var linkedContent = escaped.replace(linkRegex, function(url) {
                        return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
                    });

                    container.innerHTML = linkedContent;
                } else {
                    container.innerHTML = '<span style="font-style: italic; color: var(--muted);">This user hasn\'t written a biography yet.</span>';
                }
            }

            // Yardımcı Fonksiyonlar
            function escapeHtml(str) {
                if (str == null) return '';
                var div = document.createElement('div');
                div.textContent = String(str);
                return div.innerHTML;
            }

            function showErrorPage() {
                // Hata yönlendirmesi gerekiyorsa açılabilir
                // window.location.href = "/error?code=404";
            }
        })();
        </script>
    </body>
</html>