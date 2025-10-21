<?php
// === データベース接続設定（Railway） ===
$host = 'metro.proxy.rlwy.net';
$dbname = 'railway';
$username = 'root';
$password = 'vdVmRzsYYNYUVASOOdYqwKWiRotmWbpa';
$port = 20028;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
}
?>
