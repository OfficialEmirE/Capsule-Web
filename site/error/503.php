<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 Service Unavailable - Capsule</title>
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
            color: #F59E0B;
        }
        .error-description {
            font-size: 1.25rem;
            color: #6B7280;
        }
        .error-button {
            padding: 10px 20px;
            background-color: #F59E0B;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="error-container">
        <div class="error-title">503</div>
        <p class="error-description">Servis şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.</p>
        <a href="/" class="error-button">Ana Sayfaya Dön</a>
    </div>

</body>
</html>
