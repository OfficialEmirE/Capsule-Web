<?php
ob_start();
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set("display_errors", 0);

$config = require '../../../config.php';

echo json_encode([
    "maintenance_mode" => $config["maintenance_mode"],
    "maintenance_name" => $config["maintenance_name"],
    "maintenance_message" => $config["maintenance_message"]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);