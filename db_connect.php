<?php
// --- データベース接続設定 ---
$host = 'localhost';
$dbname = 'school_app'; // phpMyAdminで確認したデータベース名
$username = 'root';     // XAMPP標準設定
$password = '';         // パスワード未設定なら空欄

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}
?>
