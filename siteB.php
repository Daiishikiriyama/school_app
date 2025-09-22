<?php
// siteB.php  (教員用ダッシュボードの簡易サンプル)
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>サイトB（教員）</title></head>
<body>
  <h1>サイトB（教員向け）</h1>
  <p><?=htmlspecialchars($_SESSION['username'])?> さん、ようこそ。</p>
  <p><a href="logout.php">ログアウト</a></p>
  <!-- ここに入力フォームを作り、 teacher_data テーブルへ保存する処理を実装します -->
</body></html>
