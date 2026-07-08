<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        async
        defer
        ></script>
        <link rel="stylesheet" href="/assets/css/Capsule.css">
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
                        <form onsubmit="return validateTurnstile(event)" style="margin-top:5px; margin-left:5px;">
                            <!-- Username Box -->
                            <p>Username:<span style="color: red; vertical-align: super;">*</span></p>
                            <input type="text" name="username" placeholder="Username" required>
                            <!-- Email Box -->
                            <p>Email:</p>
                            <input type="email" name="email" placeholder="Email">
                            <!-- Password Box -->
                            <p>Password:<span style="color: red; vertical-align: super;">*</span></p>
                            <input type="password" placeholder="Password" name="password" required>

                            <input type="checkbox" name="vehicle" required/> I agree to the <a href="/terms" style="text-decoration: underline;">Terms of Use</a> and <a href="/privacy" style="text-decoration: underline;">Privacy Policy</a>.<span style="color: red; vertical-align: super;">*</span>

                            <div class="cf-turnstile" data-sitekey="0x4AAAAAADuaupSGzeiVWHtX"></div>
                            <input type="submit" name="register" value="Register" style="width:70px; height:25px; font-family: 'Trebuchet MS'">

                            <div style="font-size: 12px;">
                                <p>Already have an account? <a href="/auth/login" style="text-decoration: underline;">Log in</a></p>
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