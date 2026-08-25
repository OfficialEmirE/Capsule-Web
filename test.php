<?php
// test.php for testing fiture + password reset for owners
declare(strict_types=1);

session_start();

require_once __DIR__ . '/api/config.php';

$db = api_db();


// Only explicitly approved user IDs may access this test page.
$allowedTestUserIds = [1, 2, 3, 32];
if (!isset($_SESSION['user_id']) || !in_array((int)$_SESSION['user_id'], $allowedTestUserIds, true)) {
    http_response_code(403);
    exit("Forbidden");
}


$message = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = trim($_POST['user'] ?? '');


    if ($input === '') {

        $message = "User ID or username required.";

    } else {


        // ID veya username ara
        if (ctype_digit($input)) {

            $stmt = $db->prepare("
                SELECT id, username, email
                FROM users
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([
                (int)$input
            ]);

        } else {

            $stmt = $db->prepare("
                SELECT id, username, email
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([
                $input
            ]);
        }


        $user = $stmt->fetch(PDO::FETCH_ASSOC);



        if (!$user) {

            $message = "User not found.";

        } else {


            // Token oluştur
            $token = bin2hex(random_bytes(32));


            // auth.php ile aynı sistem
            $tokenHash = password_hash(
                $token,
                PASSWORD_DEFAULT
            );



            // Eski resetleri temizle
            $delete = $db->prepare("
                DELETE FROM password_resets
                WHERE user_id = ?
            ");

            $delete->execute([
                $user['id']
            ]);



            // Yeni reset kaydı
            $insert = $db->prepare("
                INSERT INTO password_resets
                (
                    user_id,
                    token_hash,
                    expires_at
                )
                VALUES
                (
                    ?,
                    ?,
                    DATE_ADD(NOW(), INTERVAL 1 HOUR)
                )
            ");


            $insert->execute([
                $user['id'],
                $tokenHash
            ]);



            $url =
                "http://capsule.my.to/auth/reset?token="
                . urlencode($token);



            $username = htmlspecialchars(
                $user['username'] ?? 'Unknown'
            );


            $email = htmlspecialchars(
                $user['email'] ?? 'No Email'
            );


            $message = "
            <div style='padding:15px;border:1px solid #ccc'>
            
            <b>Password Reset Created</b>
            
            <br><br>

            User ID:
            ".htmlspecialchars((string)$user['id'])."

            <br>

            Username:
            {$username}

            <br>

            Email:
            {$email}

            <br><br>


            Reset URL:

            <br>

            <a href='".htmlspecialchars($url)."'>
            ".htmlspecialchars($url)."
            </a>

            </div>
            ";
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Password Reset Generator</title>

<style>

body {
    font-family: Arial;
    padding:40px;
}

input {
    padding:8px;
    width:250px;
}

button {
    padding:8px 15px;
    cursor:pointer;
}

.test-panel {
    max-width:520px;
    margin-top:30px;
    padding:18px;
    border:1px solid #ccc;
    border-radius:6px;
}

.test-panel h3 {
    margin-top:0;
}

.upload-result {
    white-space:pre-wrap;
    margin-top:15px;
    padding:10px;
    background:#f5f5f5;
    border:1px solid #ddd;
    font-size:12px;
}

.uploaded-image {
    display:none;
    max-width:100%;
    aspect-ratio:16 / 9;
    object-fit:contain;
    margin-top:12px;
    background:#eee;
    border:1px solid #ddd;
}

</style>

</head>


<body>


<h2>Password Reset Generator</h2>


<form method="POST">

<input
type="text"
name="user"
placeholder="User ID or Username"
required
>


<button type="submit">
Create Reset URL
</button>


</form>


<br>


<div>

<?= $message ?>

</div>


<section class="test-panel">
    <h3>CDN Image Upload Test</h3>

    <form id="assetUploadForm" enctype="multipart/form-data">
        <input type="file" name="file" accept="image/*" required>
        <input type="hidden" name="type" value="image">
        <button type="submit">Upload Image</button>
    </form>

    <pre id="uploadResult" class="upload-result">No upload started.</pre>
    <img id="uploadedImage" class="uploaded-image" alt="Uploaded asset preview">
</section>

<script>
(function () {
    var form = document.getElementById('assetUploadForm');
    var result = document.getElementById('uploadResult');
    var preview = document.getElementById('uploadedImage');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var fileInput = form.querySelector('input[type="file"]');
        if (!fileInput.files.length) return;

        result.textContent = 'Uploading...';
        preview.style.display = 'none';

        fetch('/api/v1/assets/upload', {
            method: 'POST',
            body: new FormData(form)
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) throw new Error(data.message || 'Upload failed.');
                    return data;
                });
            })
            .then(function (data) {
                result.textContent = JSON.stringify(data, null, 2);
                if (data.asset && data.asset.id) {
                    fetch('/api/v1/assets?id=' + encodeURIComponent(data.asset.id) + '&type=image', {
                        headers: { Accept: 'application/json' }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (assetData) {
                            if (assetData.status === 'success' && assetData.asset && assetData.asset.url) {
                                preview.src = assetData.asset.url;
                                preview.style.display = 'block';
                            }
                        });
                }
            })
            .catch(function (error) {
                result.textContent = 'Error: ' + error.message;
            });
    });
}());
</script>


</body>

</html>
