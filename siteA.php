<?php
// siteA.php  (子ども用ダッシュボードの簡易サンプル)
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>サイトA（子ども）</title></head>
<body>
  <h1>サイトA（子ども向け）</h1>
  <p><?=htmlspecialchars($_SESSION['username'])?> さん、ようこそ。</p>
  <p><a href="logout.php">ログアウト</a></p>
  <!-- ここに入力フォームを作り、 student_data テーブルへ保存する処理を実装します -->
</body></html>
