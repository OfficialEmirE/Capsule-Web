<?php
$config = require 'config.php';

if ($config['maintenance_mode'] && !isset($_GET['admin'])) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Retry-After: 3600'); // 1 saat sonra tekrar dene (saniye cinsinden)

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="tr">

        <head>
            <meta charset="UTF-8">
            <title>Capsule - Güncelleniyor</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        </head>

        <body class="bg-gray-100 flex items-center justify-center min-h-screen">
            <div id="error" class="hidden bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"></div>
            <div id="warning" class="hidden bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded"></div>
            <div class="text-center bg-white shadow-lg rounded-lg p-8 max-w-md">
                <h1 class="text-2xl font-bold text-red-600 mb-4">{$config['maintenance_name']}</h1>
                <p class="text-gray-600">{$config['maintenance_message']}</p>
                <p class="text-sm text-gray-400">Yönetici giriş yaparak devam edebilir.</p>
            </div>
            <script>
                const warningElement = document.getElementById('error');
                warningElement.textContent = `Capsule Sitesi ve Sistemleri Şu Anlık Güncelleniyor! Bazı Özellikler Çalışmayabilir!`;
                warningElement.classList.remove('hidden');
            </script>
        </body>
    </html>
    HTML;

    // Kodun geri kalanının çalışmasını engellemek için mutlaka durdurun
    exit;
}
?>