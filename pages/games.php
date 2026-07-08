<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games - Capsule Beta</title>
    <link rel="stylesheet" href="/assets/css/Capsule.css">

    <?php include ROOT_PATH . 'includes/meta.php'; ?>
    <?php include ROOT_PATH . 'includes/icon.php'; ?>

    <style>
    body{
        margin:0;
        font:12px Arial;
        background:#ececec;
    }

    .games-page{
        width:970px;
        margin:20px auto;
    }

    .top{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:10px;
    }

    h1{
        margin:0;
        font-size:28px;
        font-weight:normal;
    }

    .search input{
        width:180px;
        padding:5px;
        border:1px solid #bbb;
    }

    .content{
        display:flex;
    }

    .sidebar{
        width:170px;
        background:#fff;
        border:1px solid #ccc;
        padding:10px;
    }

    .sidebar h3{
        margin:10px 0 5px;
        font-size:13px;
    }

    .sidebar a{
        display:block;
        padding:2px 0;
        color:#06c;
        text-decoration:none;
    }

    .sidebar a:hover{
        text-decoration:underline;
    }

    .main{
        flex:1;
        margin-left:10px;
        background:#fff;
        border:1px solid #ccc;
        padding:10px;

        display:flex;
        flex-wrap:wrap;
        gap:15px;
        align-content:flex-start;
    }

    .game{
        width:150px;
        margin:0;
    }

    .game img{
        width:150px;
        height:100px;
        border:1px solid #aaa;
        background:#ddd;
    }

    .game-title{
        margin-top:5px;
        font-weight:bold;
    }

    .game-title a{
        color:#06c;
        text-decoration:none;
    }

    .creator{
        color:#777;
        font-size:11px;
    }

    .stats{
        color:#555;
        font-size:10px;
    }

    .pages{
        clear:both;
        text-align:center;
        padding-top:10px;
    }

    .pages a{
        margin:0 3px;
        color:#06c;
        text-decoration:none;
    }
    </style>

</head>
<body>

<?php include ROOT_PATH . 'includes/header.php'; ?>

<div class="games-page">

    <div class="top">
        <h1>Games</h1>

        <div class="search">
            <input type="text" placeholder="Search Games">
        </div>
    </div>

    <div class="content">

        <div class="sidebar">

            <h3>Sorted By</h3>

            <a href="#">Popular</a>
            <a href="#">Featured</a>
            <a href="#">Top Rated</a>
            <a href="#">New</a>

            <h3>Genres</h3>

            <a href="#">All</a>
            <a href="#">Building</a>
            <a href="#">Adventure</a>
            <a href="#">Town</a>
            <a href="#">Horror</a>

        </div>

        <div class="main">

            <?php for($i=1;$i<=18;$i++): ?>

            <div class="game">
                <img src="https://placehold.co/150x100">
                <div class="game-title">
                    <a href="#">Capsule Game <?=$i?></a>
                </div>
                <div class="creator">
                    by Builder
                </div>
                <div class="stats">
                    👥 24 Playing<br>
                    👁 154,322 Visits
                </div>
            </div>

            <?php endfor; ?>

            <div class="pages">
                <a href="#">&lt;</a>
                <a href="#"><b>1</b></a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#">4</a>
                <a href="#">&gt;</a>
            </div>

        </div>

    </div>

</div>

<?php include ROOT_PATH . 'includes/bottom.php'; ?>

</body>
</html>