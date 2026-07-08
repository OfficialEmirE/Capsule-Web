<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">
        <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        async
        defer
        ></script>
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
                        <form onsubmit="return validateTurnstile(event)" style="margin-top:5px; margin-left:5px;">
                            <p>Username or E-mail:</p>
                            <input type="text" name="username" placeholder="Username or E-mail" required>
                            <p>Password:</p>
                            <input type="password" placeholder="Password" name="password" required>
                            <div class="cf-turnstile" data-sitekey="0x4AAAAAADuaupSGzeiVWHtX"></div>
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
        <script src="/assets/js/Turnstile.js"></script>
    </body>
</html>