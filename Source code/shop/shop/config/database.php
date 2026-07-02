<?php
/**
 * Kết nối cơ sở dữ liệu (PDO)
 * Sửa lại thông tin bên dưới cho khớp với máy của bạn (XAMPP/Laragon...).
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'technest_shop');
define('DB_USER', 'root');     // XAMPP mặc định: root
define('DB_PASS', '');         // XAMPP mặc định: rỗng

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Không kết nối được CSDL: ' . $e->getMessage()
        . '<br>Hãy kiểm tra lại file config/database.php và đảm bảo đã import database.sql.');
}
