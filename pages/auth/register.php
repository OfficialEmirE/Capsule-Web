<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register - Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">
        <style>
            .password-wrap{position:relative;display:inline-block;width:100%;max-width:100%;}
            .password-wrap input{padding-right:38px;}
            .password-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;cursor:pointer;color:#777;padding:4px;display:flex;align-items:center;justify-content:center;}
            .password-toggle:hover{color:#111;}
.eye-icon{display:flex;align-items:center;justify-content:center;}
.eye-icon svg{display:block;}
            .eye-icon{display:flex;align-items:center;justify-content:center;}
            .eye-icon svg{display:block;}
        </style>
    </head>
    <body>
        <?php include ROOT_PATH . 'includes/header.php'; ?>
        
        <div class="main-container"> 
            <div class="top-row">
                <div class="login-box">
                    <div class="login-page">
                        <div>
                            <h1>&nbsp;Register</h1>
                        </div>
                        <form onsubmit="handleAuthSubmit(event, 'register')" style="margin-top:5px; margin-left:5px;">
                            <!-- Username Box -->
                            <p>Username:<span style="color: red; vertical-align: super;">*</span></p>
                            <input type="text" name="username" placeholder="Username" required>
                            <!-- Email Box -->
                            <p>Email:</p>
                            <input type="email" name="email" placeholder="Email">
                            <!-- Password Box -->
                            <p>Password:<span style="color: red; vertical-align: super;">*</span></p>
                            <div class="password-wrap">
                                <input type="password" placeholder="Password" name="password" required>
                                <button type="button" class="password-toggle" aria-label="Show password">
                                    <span class="eye-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></span>
                                </button>
                            </div>
                            
                            <p>
                                <input type="checkbox" name="terms" required/> I agree to the <a href="/termsofuse" style="text-decoration: underline;">Terms of Use</a> and <a href="/privacypolicy" style="text-decoration: underline;">Privacy Policy</a>.<span style="color: red; vertical-align: super;">*</span>
                            </p>
                            
                            <div>
                                <input type="submit" name="register" value="Register" style="width:70px; height:25px; font-family: 'Trebuchet MS'">
                            </div>

                            <div style="font-size: 12px; margin-top: 10px;">
                                <p>Already have an account? <a href="/auth/login" style="text-decoration: underline;">Log in</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include ROOT_PATH . 'includes/bottom.php'; ?>

        <script>
            document.querySelectorAll('.password-toggle').forEach(button => {
                button.addEventListener('click', () => {
                    const input = button.parentElement.querySelector('input');
                    input.type = input.type === 'password' ? 'text' : 'password';
                });
            });
        </script>
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