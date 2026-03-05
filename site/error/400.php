<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>400 - Capsule</title>
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --line:#dedede; --text:#202020; --muted:#5f5f5f; }
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;font-family:"Nunito","Segoe UI",sans-serif;color:var(--text);background:#fff}
        .layout{min-height:100vh;display:grid;grid-template-rows:auto 1fr}
        .topbar{display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--line)}
        .brand img{height:44px}
        .content{display:grid;place-items:center;padding:20px}
        .card{max-width:680px;text-align:center;border:1px solid var(--line);border-radius:16px;padding:28px 22px}
        .code{font-size:72px;line-height:1;margin:0 0 10px}
        .title{margin:0 0 8px;font-size:30px}
        .desc{margin:0 0 18px;color:var(--muted)}
        .btn{display:inline-block;text-decoration:none;border:1px solid #111;background:#111;color:#fff;border-radius:10px;padding:10px 14px;font-weight:800}
    </style>
</head>
<body>
    <main class="layout">
        <header class="topbar"><a class="brand" href="../home.php"><img src="../CapsuleLogo.png" alt="Capsule"></a></header>
        <section class="content">
            <article class="card">
                <p class="code">400</p>
                <h1 class="title">Hatalı İstek</h1>
                <p class="desc">İstek formatı geçersiz veya eksik.</p>
                <a class="btn" href="../home.php">Ana Sayfaya Dön</a>
            </article>
        </section>
    </main>
</body>
</html>