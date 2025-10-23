<?php
session_start();
require_once 'db_connect.php';

// ログインユーザー名
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_id'])) {
    echo "ログイン情報が見つかりません。";
    exit;
}

$user_id = $_SESSION['user_id'];
$class_id = $_SESSION['class_id'];
$name = $_SESSION['user_name'];

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_entries 
            (user_id, class_id, time_category, how_use, what_use, want_do, 
             how_use2, what_use2, want_do2, 
             how_use3, what_use3, want_do3, 
             free_text_break, free_text_class, free_text_home)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_id,
            $class_id,
            $_POST['time_category'],
            $_POST['how_use'],
            $_POST['what_use'],
            $_POST['want_do'],
            $_POST['how_use2'] ?? null,
            $_POST['what_use2'] ?? null,
            $_POST['want_do2'] ?? null,
            $_POST['how_use3'] ?? null,
            $_POST['what_use3'] ?? null,
            $_POST['want_do3'] ?? null,
            $_POST['free_text_break'] ?? null,
            $_POST['free_text_class'] ?? null,
            $_POST['free_text_home'] ?? null
        ]);

        header("Location: siteC.php?msg=success");
        exit();

    } catch (PDOException $e) {
        $message = "データベースエラー: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>理想のChromebookの使い方入力</title>
<link rel="stylesheet" href="style.css">
<style>
body { font-family: "Yu Gothic", sans-serif; margin: 40px; background-color: #f8f9fa; }
.form-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 90%; max-width: 800px; margin: 0 auto; }
select, textarea, button { width: 100%; padding: 10px; margin-top: 10px; font-size: 16px; }
.add-section { color: #007bff; cursor: pointer; margin-top: 15px; display: inline-block; }
.section-block { border-top: 1px solid #ccc; margin-top: 20px; padding-top: 10px; }
h2 { color: #333; margin-top: 30px; }
</style>
</head>
<body>

<h1>こんにちは、<?= htmlspecialchars($name) ?> さん</h1>
<h2>理想のChromebookの使い方を入力しましょう</h2>

<div class="form-container">
<form method="post">

<!-- 1つ目（必須） -->
<div class="section-block">
<h3>1つ目（必須）</h3>
<label>どの時間ですか？</label>
<select name="time_category" required>
  <option value="">選択してください</option>
  <option value="休み時間">休み時間</option>
  <option value="授業の時間">授業の時間</option>
  <option value="家での時間">家での時間</option>
</select>

<label>どのように</label>
<select name="how_use" required>
  <option value="">選択してください</option>
  <option value="学習に必要だと思った時に">学習に必要だと思った時に</option>
  <option value="振り返りの時に">振り返りの時に</option>
  <option value="好きなときに">好きなときに</option>
</select>

<label>なにを</label>
<select name="what_use" required>
  <option value="">選択してください</option>
  <option value="YouTube">YouTube</option>
  <option value="SNS">SNS</option>
  <option value="カメラ">カメラ</option>
  <option value="スクラッチ">スクラッチ</option>
  <option value="ゲーム">ゲーム</option>
  <option value="音楽">音楽</option>
  <option value="スライド">スライド</option>
  <option value="キャンバス">キャンバス</option>
  <option value="タイピング練習">タイピング練習</option>
  <option value="デジタル教科書">デジタル教科書</option>
  <option value="コラボノート">コラボノート</option>
  <option value="絵">絵</option>
  <option value="アプリ">アプリ</option>
  <option value="動画">動画</option>
</select>

<label>したい</label>
<select name="want_do" required>
  <option value="">選択してください</option>
  <option value="聞く">聞く</option>
  <option value="見る">見る</option>
  <option value="使う">使う</option>
  <option value="学ぶ">学ぶ</option>
  <option value="作る">作る</option>
  <option value="入れる">入れる</option>
  <option value="撮る">撮る</option>
  <option value="描く">描く</option>
  <option value="調べる">調べる</option>
</select>
</div>

<!-- 2つ目 -->
<div class="section-block">
<h3>2つ目（任意）</h3>
<select name="how_use2"><option value="">どのように</option>...</select>
<select name="what_use2"><option value="">なにを</option>...</select>
<select name="want_do2"><option value="">したい</option>...</select>
</div>

<!-- 3つ目 -->
<div class="section-block">
<h3>3つ目（任意）</h3>
<select name="how_use3"><option value="">どのように</option>...</select>
<select name="what_use3"><option value="">なにを</option>...</select>
<select name="want_do3"><option value="">したい</option>...</select>
</div>

<!-- 自由記述 -->
<h2>他の人の使い方で気になったこと</h2>
<label>休み時間に...</label>
<textarea name="free_text_break" rows="3"></textarea>

<label>授業中に...</label>
<textarea name="free_text_class" rows="3"></textarea>

<label>家で...</label>
<textarea name="free_text_home" rows="3"></textarea>

<button type="submit">送信する</button>
</form>
</div>
</body>
</html>
