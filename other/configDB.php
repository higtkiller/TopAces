<?php
// DEBUG - Remove after testing
echo "DB_HOST from env: " . getenv('DB_HOST') . "<br>";
echo "DB_NAME from env: " . getenv('DB_NAME') . "<br>";
echo "DB_USER from env: " . getenv('DB_USER') . "<br>";
echo "DB_PORT from env: " . getenv('DB_PORT') . "<br>";
die("Debug stop");

// Railway environment variables or local fallback
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'topaces';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$port = getenv('DB_PORT') ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
