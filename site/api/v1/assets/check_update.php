<?php
// 1. Desteklenen Depo Listesi (Buraya istediğin kadar ekle)
$repos = [
    "capsule" => "OfficialEmirE/Capsule",
    "engine" => "Ramazanenescik04/DikenEngine",
    "launcher" => "Ramazanenescik04/Capsule-Launcher",
    "lwjgl2" => "Ramazanenescik04/lwjgl2-custom"
];

// 2. İstek kontrolü
$repo_key = $_GET['name'] ?? '';

if (!array_key_exists($repo_key, $repos)) {
    header('Content-Type: application/json', true, 404);
    echo json_encode(["error" => "Depo bulunamadi"]);
    exit;
}

$repo_path = $repos[$repo_key];
$api_url = "https://api.github.com/repos/$repo_path/releases/latest";

// Her depo için benzersiz dosya isimleri
$cache_file = "cache_" . $repo_key . ".json";
$etag_file = "etag_" . $repo_key . ".txt";

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Capsule-Update-Bridge\r\n"
    ]
];

if (file_exists($etag_file)) {
    $opts["http"]["header"] .= "If-None-Match: " . file_get_contents($etag_file) . "\r\n";
}

$context = stream_context_create($opts);
$response_body = @file_get_contents($api_url, false, $context);
$status_line = $http_response_header[0] ?? '';

if (strpos($status_line, '200') !== false) {
    // Yeni veri: ETag ve Body güncelle
    foreach ($http_response_header as $header) {
        if (stripos($header, 'ETag:') === 0) {
            file_put_contents($etag_file, trim(str_ireplace('ETag:', '', $header)));
        }
    }
    file_put_contents($cache_file, $response_body);
    $output = $response_body;
} else {
    // 304 veya hata: Cache'den oku
    $output = file_exists($cache_file) ? file_get_contents($cache_file) : json_encode(["error" => "Veri yok"]);
}

header('Content-Type: application/json');
echo $output;