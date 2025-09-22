<?php
// db.php - PDOで接続しておきます
$host = '127.0.0.1';
$db   = 'school_app';
$user = 'root';
$pass = ''; // XAMPPのデフォルト。実運用では変更してください
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // 開発時はエラーを確認しても良いですが、本番ではメッセージを出しすぎないこと
    http_response_code(500);
    echo "DB接続エラー";
    exit;
}
