<?php
$host = 'localhost';
$dbname = 'school_app';  // ← ここを変更！
$username = 'root';      // ← XAMPPの初期設定
$password = '';          // ← 初期設定は空欄

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
}
?>
