<?php
$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$port = getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}
?>