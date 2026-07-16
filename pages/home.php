<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Capsule Beta</title>
        <?php include ROOT_PATH . 'includes/meta.php'; ?>
        <?php include ROOT_PATH . 'includes/icon.php'; ?>
        <link rel="stylesheet" href="/assets/css/Capsule.css">

        <style>
        /* Discord widget'a erişilemediğinde gösterilen yedek içerik */
        .discord-fallback{
            width:100%;
            height:100%;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:12px;
            color:#dcddde;
            text-align:center;
            padding:20px;
        }

        .discord-fallback p{
            font-size:13px;
            color:#b9bbbe;
            margin:0;
        }

        .btn-join-discord{
            display:inline-block;
            background:#5865f2;
            color:#fff;
            font-size:13px;
            font-weight:bold;
            padding:8px 18px;
            border-radius:4px;
            text-decoration:none;
        }

        .btn-join-discord:hover{
            background:#4752c4;
        }
        </style>
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
                <!-- Sınıf ismi discord-widget olarak değiştirildi -->
                <div class="discord-widget" id="discordWidget">
                    <iframe id="discordIframe" src="https://discord.com/widget?id=1520893978201817218&theme=dark" allowtransparency="true" frameborder="0" sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"></iframe>
                </div>
            </div>
        </div>

        <script src="/assets/js/DiscordWidget.js" data-guild-id="1520893978201817218" data-invite-url="https://discord.gg/YOUR_INVITE_CODE" data-timeout="6000"></script>

        <?php include ROOT_PATH . 'includes/bottom.php'; ?>
    </body>
</html>