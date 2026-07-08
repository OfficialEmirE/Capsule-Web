<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/meta.php'; ?>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">
    </head>
    <body>
        <?php include ROOT_PATH . 'includes/header.php'; ?>
        
        <div class="main-container"> 
            <div class="left-column">
                <div class="featured-game">
                    <h2>Welcome to Capsule Beta!</h2>
                    <p>Hello User. Welcome to the <b>Capsule Beta Program</b>. <br>Create your own game or play games with your friends! <br>If you find any bugs, join our Discord Server! <br>Create your account and get started!</p>
                    <div class="video-box">
                        <iframe width="100%" height="100%" src="https://www.youtube.com/embed/ikTOS3vy3H8" title="Capsule - Football" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
            <div class="right-column">
                <div class="login-box">
                    <div>
                        <h2>&nbsp;Login</h2>
                    </div>
                    <form style="margin-top:5px; margin-left:5px;">
                        <p>Username:</p>
                        <input type="text" name="username" placeholder="Username">
                        <p>Password:</p>
                        <input type="password" placeholder="Password" name="password">
                        <center>
                            <input type="submit" name="login" value="Login" style="margin-left: -5px; width:70px; height:25px; font-family: 'Trebuchet MS'">
                        </center>
                        <center style="font-size: 12px;">
                            <p>Don't have an account? <a href="/auth/register" style="text-decoration: underline;">Sign up</a></p>
                        </center>
                    </form>
                </div>
                
                <!-- Sınıf ismi discord-widget olarak değiştirildi -->
                <div class="discord-widget">
                    <iframe src="https://discord.com/widget?id=1520893978201817218&theme=dark" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
                </div>
            </div>
        </div>
        
        <?php include ROOT_PATH . 'includes/bottom.php'; ?>
    </body>
</html>