<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/*
|--------------------------------------------------------------------------
| CAPSULE PLATFORM CONFIGURATION
|--------------------------------------------------------------------------
*/

// Hata raporlama ayarları (Geliştirme aşamasında açık, canlıda kapatılabilir)
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('MAIL_HOST', 'free.mboxhosting.com');
define('MAIL_USERNAME', 'noreply@capsule.my.to');
define('MAIL_PASSWORD', 'Er030303?!');
define('MAIL_FROM', 'noreply@capsule.my.to');

/**
 * Veritabanı bağlantısını sağlayan ve PDO instance döndüren çekirdek fonksiyon
 */
function api_db(): PDO
{
    $host    = 'fdb1029.awardspace.net'; 
    $dbName  = '4771164_db'; 
    $dbUser  = '4771164_db'; 
    $dbPass  = 'Er030303?!'; 
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$dbName;charset=$charset";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $dbUser, $dbPass, $options);
    } catch (PDOException $e) {
        throw new Exception('Database Connection Failed: ' . $e->getMessage(), 500);
    }
}