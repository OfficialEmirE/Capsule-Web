<?php
return [
    // Veritabanı ayarları
    'db_host' => 'sql206.infinityfree.com',
    'db_name' => 'if0_38891626_capsule_db',
    'db_user' => 'if0_38891626',
    'db_pass' => 'Lvh3cSv9hZj',

    'maintenance_mode' => false, // false yaparsan bakım modu kapanır
    'maintenance_name' => 'Siteye Erişiminiz Yok!',
    'maintenance_message' => 'Capsule şu anlık erişimi kapanmıştır!',

    'announcement_mode' => true,
    'announcement_message' => 'Capsule Apilerde Sorun Yaşanıyor! Rahatsızlık İçin Özür Dileriz.',
];

/*
declare(strict_types=1);

define('MAINTENANCE_MODE', false);

/
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
| Update these values for your local or production database.


define('DB_HOST', 'sql206.infinityfree.com');
define('DB_PORT', '3306');
define('DB_NAME', 'if0_38891626_capsule_db');
define('DB_USER', 'if0_38891626');
define('DB_PASS', 'Lvh3cSv9hZj');
define('DB_CHARSET', 'utf8mb4');


 * Shared PDO connection helper for API endpoints.

function api_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
*/