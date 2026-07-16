<nav class="navbar">
    <div class="navbar-inner">
        <div style="display:flex;align-items:center;">
            <a href="https://capsule.my.to" class="nav-logo">
                <img src="/assets/images/CapsuleLogoBeta.png" alt="Capsule Logo" style="height:28px;">
            </a>
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/games">Games</a></li>
                <li><a href="#">Create</a></li>
                <li><a href="/avatar">Avatar</a></li>
                <li><a href="/download">Download</a></li>
            </ul>
        </div>
        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Giriş Yapılmışsa Gösterilecek Kısım -->
                <span class="nav-welcome" style="margin-right: 5px; font-weight: bold; color: #333; display: inline-flex; align-items: center;">
                    <a href="/users/<?php echo $_SESSION['user_id']; ?>"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
                </span>
                <!-- href yerine JavaScript tetikleyen bir buton yapısı kuruyoruz -->
                <a href="#" onclick="handleLogout(event)" class="btn-logout" style="text-decoration: none; padding: 5px 10px; border: 1px solid #ccc; border-radius: 3px; background: #f8f8f8; color: #333; font-size: 13px; cursor: pointer;">Log Out</a>
            <?php else: ?>
                <!-- Giriş Yapılmamışsa Gösterilecek Kısım -->
                <a href="/auth/register" class="btn-signup">Sign Up</a>
                <span class="nav-separator">or</span>
                <a href="/auth/login" class="btn-login">Log In</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
/**
 * Oturumu güvenli bir şekilde kapatıp kullanıcıyı anasayfaya yönlendirir.
 */
async function handleLogout(event) {
    event.preventDefault();
    
    try {
        const response = await fetch('/api/v1/auth/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8'
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.status === 'success') {
            // Başarılıysa sayfayı anasayfaya yönlendir veya yenile
            window.location.href = '/';
        } else {
            console.error('Logout failed:', result.message);
            // Hata durumunda fallback olarak yine de sayfayı yenileyebiliriz
            window.location.reload();
        }
    } catch (error) {
        console.error('Network error during logout:', error);
        window.location.reload();
    }
}
</script>