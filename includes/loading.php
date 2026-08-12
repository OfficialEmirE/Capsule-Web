<?php
$capsuleLaunchToken = session_id();
?>
<div id="capsuleLoading" class="capsule-loading" hidden aria-hidden="true">
    <div class="capsule-loading-card" role="dialog" aria-modal="true" aria-labelledby="capsuleLoadingTitle">
        <button id="capsuleLoadingClose" class="capsule-loading-close" type="button" aria-label="Close">&times;</button>
        <div id="capsuleLoadingSpinner" class="capsule-loading-spinner" aria-hidden="true"></div>
        <h2 id="capsuleLoadingTitle">Loading...</h2>
        <p id="capsuleLoadingText">Opening Capsule.</p>
        <a id="capsuleLoadingDownload" class="capsule-loading-download" href="/download" style="display: none;" hidden>Download Launcher</a>
    </div>
</div>
<style>
    .capsule-loading[hidden] { display:none; }
    .capsule-loading { position:fixed; inset:0; z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(51,51,51,.42); }
    .capsule-loading-card { position:relative; width:min(360px,100%); padding:28px 24px 24px; border:1px solid var(--panel-border); border-radius:6px; background:var(--panel); box-shadow:0 8px 30px rgba(0,0,0,.2); text-align:center; color:var(--text); }
    .capsule-loading-card h2 { margin:8px 0 6px; font-size:20px; }
    
    /* Değişiklik: Yazı boyutu büyütüldü ve üst boşluk eklendi */
    .capsule-loading-card p { margin:15px 0 0; color:var(--muted); font-size:18px; font-weight:500; }
    
    .capsule-loading-close { position:absolute; top:7px; right:10px; border:0; background:transparent; color:var(--muted); font-size:25px; line-height:1; cursor:pointer; }
    .capsule-loading-spinner { width:30px; height:30px; margin:0 auto 12px; border:3px solid var(--panel-border); border-top-color:var(--button-primary); border-radius:50%; animation:capsule-loading-spin .8s linear infinite; }
    .capsule-loading-download { display:inline-block; margin-top:18px; padding:9px 15px; border-radius:3px; background:var(--button-primary); color:#fff; font-size:12px; font-weight:bold; text-decoration:none; }
    @keyframes capsule-loading-spin { to { transform:rotate(360deg); } }
</style>
<script>
(function () {
    var modal = document.getElementById('capsuleLoading');
    var close = document.getElementById('capsuleLoadingClose');
    var title = document.getElementById('capsuleLoadingTitle');
    var spinner = document.getElementById('capsuleLoadingSpinner');
    var text = document.getElementById('capsuleLoadingText');
    var download = document.getElementById('capsuleLoadingDownload');
    var launchToken = <?php echo json_encode($capsuleLaunchToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    function hideLoading() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        download.style.display = 'none';
    }

    window.openCapsuleLauncher = function (gameId, studio) {
        if (!launchToken) {
            window.location.href = '/auth/login';
            return;
        }
        var id = Number(gameId) || 0;
        var isStudio = Boolean(studio);
        var protocolUrl = 'capsule://launch?token=' + encodeURIComponent(launchToken) + '&gameId=' + id + '&studio=' + (isStudio ? 'true' : 'false');
        
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        title.style.display = 'block';
        spinner.style.display = 'block';
        
        // Değişiklik: İngilizce metinler atandı
        text.textContent = isStudio ? 'Opening Capsule Studio...' : 'Opening Capsule...';
        download.hidden = true;
        download.style.display = 'none';

        if (!isStudio && id > 0) {
            var visitPayload = new URLSearchParams({ game_id: String(id), action: 'visit' });
            var visitSent = navigator.sendBeacon && navigator.sendBeacon('/api/v1/games/engagement', visitPayload);
            if (!visitSent) {
                fetch('/api/v1/games/engagement', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ game_id: id, action: 'visit' }),
                    keepalive: true
                }).catch(function () {});
            }
        }
        
        try {
            window.location.href = protocolUrl;
        } catch (e) {
            console.error("Protokol yönlendirme hatası:", e);
        }
        
        window.setTimeout(function () {
            if (!modal.hidden) {
                title.style.display = 'none';
                spinner.style.display = 'none';
                
                // Değişiklik: 10 saniye sonra çıkacak uyarı İngilizce yapıldı
                text.textContent = 'You need Capsule to play.';
                download.hidden = false;
                download.style.display = 'inline-block';
            }
        }, 10000);
    };

    close.addEventListener('click', hideLoading);
    modal.addEventListener('click', function (event) { if (event.target === modal) hideLoading(); });
})();
</script>
