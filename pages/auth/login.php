<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Capsule Beta</title>
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
                            <h1>&nbsp;Login</h1>
                        </div>
                        <form onsubmit="handleAuthSubmit(event, 'login')" style="margin-top:5px; margin-left:5px;">
                            <p>Username or E-mail:</p>
                            <input type="text" name="username" placeholder="Username or E-mail" required>
                            <p>Password:</p>
                            <div class="password-wrap">
                                <input type="password" placeholder="Password" name="password" required>
                                <button type="button" class="password-toggle" aria-label="Show password">
                                    <span class="eye-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></span>
                                </button>
                            </div>
                            <input type="submit" name="login" value="Login" style="width:70px; height:25px; font-family: 'Trebuchet MS'">
                            <div style="font-size: 12px;">
                                <p>Don't have an account? <a href="/auth/register" style="text-decoration: underline;">Sign up</a></p>
                                <p><a href="/auth/reset" style="text-decoration: underline;">Reset Password</a></p>
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
                    const visible = input.type === 'text';
                    input.type = visible ? 'password' : 'text';
                    button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
                    button.querySelector('.eye-icon').innerHTML = visible ? '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>' : '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 4.2A11.5 11.5 0 0 1 12 4c6.5 0 10 8 10 8a17.3 17.3 0 0 1-3.1 4.1"/><path d="M6.1 6.1C3.7 7.7 2 12 2 12s3.5 8 10 8a11.4 11.4 0 0 0 3.3-.5"/></svg>';
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
                event.preventDefault();

                const form = event.target;
                const formData = new FormData(form);
                const data = {};

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
                        const urlParams = new URLSearchParams(window.location.search);
                        const nextUrl = urlParams.get('next');

                        if (nextUrl && nextUrl.startsWith('/')) {
                            window.location.href = nextUrl;
                        } else {
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
