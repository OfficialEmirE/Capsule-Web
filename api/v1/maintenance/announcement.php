<?php
ob_start();
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors", 0);

$config = require '../../config.php';

echo json_encode([
    "announcement_mode" => $config["announcement_mode"],
    "announcement_message" => $config["announcement_message"]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);