<?php
session_start();
require_once 'db_connect.php';

// 仮のログイン情報（本来はログインページから受け取る）
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = "３"; // テスト用
}

$name = $_SESSION['user_name'];

// フォーム送信処理
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $stmt = $pdo->prepare("INSERT INTO children_data 
            (time_category, how_use, what_use, want_do, how_use2, what_use2, want_do2)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['time_category'],
            $_POST['how_use'],
            $_POST['what_use'],
            $_POST['want_do'],
            $_POST['how_use2'] ?? null,
            $_POST['what_use2'] ?? null,
            $_POST['want_do2'] ?? null
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
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>理想のChromebookの使い方入力</title>
    <style>
        body { font-family: "Yu Gothic", sans-serif; margin: 40px; background-color: #f8f9fa; }
        h1 { color: #333; }
        .error { color: red; margin-bottom: 10px; }
        .form-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 500px; }
        select, button { width: 100%; padding: 10px; margin-top: 10px; font-size: 16px; }
        .add-section { color: #007bff; cursor: pointer; margin-top: 15px; display: inline-block; }
    </style>
</head>
<body>

<h1>こんにちは、<?= htmlspecialchars($name) ?> さん</h1>
<h2>理想のChromebookの使い方を入力しましょう</h2>

<?php if ($message): ?>
    <p class="error"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<div class="form-container">
<form method="post">
    <label>どの時間の理想のクロムの使い方を入力したいですか？</label>
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

    <div id="extra-section" style="display:none;">
        <h3>＋２つ目の入力</h3>
        <select name="how_use2">
            <option value="">選択してください</option>
            <option value="学習に必要だと思った時に">学習に必要だと思った時に</option>
            <option value="振り返りの時に">振り返りの時に</option>
            <option value="好きなときに">好きなときに</option>
        </select>
        <select name="what_use2">
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
        <select name="want_do2">
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

    <span class="add-section" onclick="document.getElementById('extra-section').style.display='block';">
        ＋２つ目も入力する
    </span>

    <button type="submit">送信する</button>
</form>
</div>

</body>
</html>
