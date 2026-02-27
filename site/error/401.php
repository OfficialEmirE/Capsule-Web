<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 Unauthorized - Capsule</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .error-container {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
        }
        .error-title {
            font-size: 5rem;
            font-weight: 700;
            color: #EF4444;
        }
        .error-description {
            font-size: 1.25rem;
            color: #6B7280;
        }
        .error-button {
            padding: 10px 20px;
            background-color: #EF4444;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="error-container">
        <div class="error-title">401</div>
        <p class="error-description">Bu sayfayı görmek için yetkiniz yok. Lütfen giriş yapın.</p>
        <a href="/login" class="error-button">Giriş Yap</a>
    </div>

</body>
</html>
