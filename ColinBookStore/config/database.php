<?php
/** Konfigurasi pusat: kredensial database, URL aplikasi, dan session cookie. */

declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'colinbookstore');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/ColinBookStore');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Koneksi database gagal. Import database/colinbookstore.sql dan periksa konfigurasi MySQL.');
}
