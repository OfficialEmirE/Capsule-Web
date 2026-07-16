<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">
    </head>
    <body>
        <?php include ROOT_PATH . 'includes/header.php'; ?>
        
        <div class="main-container"> 
            <div class="top-row">
                <div class="login-box">
                    <div class="login-page">
                        <div>
                            <h1>&nbsp;Login</h1>
                        </div>
                        <form onsubmit="handleAuthSubmit(event, 'login')" style="margin-top:5px; margin-left:5px;">
                            <p>Username or E-mail:</p>
                            <input type="text" name="username" placeholder="Username or E-mail" required>
                            <p>Password:</p>
                            <input type="password" placeholder="Password" name="password" required>
                            <input type="submit" name="login" value="Login" style="width:70px; height:25px; font-family: 'Trebuchet MS'">
                            <div style="font-size: 12px;">
                                <p>Don't have an account? <a href="/auth/register" style="text-decoration: underline;">Sign up</a></p>
                                <!-- <p><a href="/auth/reset" style="text-decoration: underline;">Reset Password</a></p> -->
                            </div>
                        </form>       
                    </div>
                </div>
            </div>
        </div>
        
        <?php include ROOT_PATH . 'includes/bottom.php'; ?>
        <!-- Form Gönderim JavaScript Kodu -->
        <script>
            /**
             * Capsule Auth handler for Login and Register processes.
             * Handles the forms asynchronously and redirects dynamically based on 'next' parameter.
             */
            async function handleAuthSubmit(event, action) {
                event.preventDefault(); // Sayfanın klasik form postuyla yenilenmesini engelliyoruz

                const form = event.target;
                const formData = new FormData(form);
                const data = {};

                // Form inputlarını JSON objesine çeviriyoruz
                formData.forEach((value, key) => {
                    if (action === 'login' && key === 'username') {
                        data['username_or_email'] = value.trim();
                    } else if (typeof value === 'string') {
                        data[key] = value.trim();
                    } else {
                        data[key] = value;
                    }
                });

                try {
                    const response = await fetch(`/api/v1/auth/${action}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json; charset=utf-8'
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        // URL'deki "next" parametresini okuyoruz (örn: /auth/login?next=%2Favatar)
                        const urlParams = new URLSearchParams(window.location.search);
                        const nextUrl = urlParams.get('next');

                        // Eğer "next" parametresi varsa ve güvenli bir yönlendirmeyse (harici domainleri engellemek için '/' ile başlamalı)
                        if (nextUrl && nextUrl.startsWith('/')) {
                            window.location.href = nextUrl;
                        } else {
                            // "next" yoksa varsayılan olarak anasayfaya yönlendir
                            window.location.href = '/';
                        }
                    } else {
                        alert(result.message || 'An error occurred during authentication.');
                    }
                } catch (error) {
                    console.error('Authentication error:', error);
                    alert('A network error occurred. Please try again.');
                }
            }
        </script>
    </body>
</html>