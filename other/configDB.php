<?php
// Try Railway's native MySQL variables first, then our custom ones, then local fallback
$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'topaces';
$username = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
