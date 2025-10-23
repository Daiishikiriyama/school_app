<?php
session_start();
require_once 'db_connect.php';

// -------------------------------
// ログインチェック（生徒のみアクセス可）
// -------------------------------
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'student'
) {
    echo "このページは生徒のみがアクセスできます。";
    exit;
}

$user_id  = $_SESSION['user_id'];
$name     = $_SESSION['user_name'] ?? '未設定';
$class_id = $_SESSION['class_id'] ?? null;

// -------------------------------
// フォーム送信処理
// -------------------------------
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_entries 
            (user_id, class_id,
             time_category1, how_use1, what_use1, want_do1,
             time_category2, how_use2, what_use2, want_do2,
             time_category3, how_use3, what_use3, want_do3,
             free_rest, free_class, free_home)
            VALUES 
            (:user_id, :class_id,
             :time1, :how1, :what1, :want1,
             :time2, :how2, :what2, :want2,
             :time3, :how3, :what3, :want3,
             :free_rest, :free_class, :free_home)
        ");

        $stmt->execute([
            ':user_id'   => $user_id,
            ':class_id'  => $class_id,
            ':time1'     => $_POST['time_category1'] ?? null,
            ':how1'      => $_POST['how_use1'] ?? null,
            ':what1'     => $_POST['what_use1'] ?? null,
            ':want1'     => $_POST['want_do1'] ?? null,
            ':time2'     => $_POST['time_category2'] ?? null,
            ':how2'      => $_POST['how_use2'] ?? null,
            ':what2'     => $_POST['what_use2'] ?? null,
            ':want2'     => $_POST['want_do2'] ?? null,
            ':time3'     => $_POST['time_category3'] ?? null,
            ':how3'      => $_POST['how_use3'] ?? null,
            ':what3'     => $_POST['what_use3'] ?? null,
            ':want3'     => $_POST['want_do3'] ?? null,
            ':free_rest' => $_POST['free_rest'] ?? null,
            ':free_class'=> $_POST['free_class'] ?? null,
            ':free_home' => $_POST['free_home'] ?? null
        ]);

        header("Location: siteC.php?msg=success");
        exit();
    } catch (PDOException $e) {
        $message = "データベースエラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>理想のChromebookの使い方入力</title>
    <style>
        body { font-family: "Yu Gothic", sans-serif; margin: 40px; background-color: #f8f9fa; }
        h1, h2 { color: #333; }
        .form-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 750px; margin:auto; }
        select, textarea, button { width: 100%; padding: 10px; margin-top: 10px; font-size: 16px; }
        textarea { resize: vertical; height: 80px; }
        fieldset { border: 1px solid #ccc; padding: 15px; margin-top: 20px; border-radius: 8px; }
        legend { font-weight: bold; color: #0d47a1; }
        button { margin-top: 20px; padding: 12px 18px; background-color: #0d5bd7; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #0948a1; }
    </style>
</head>
<body>

<h1>こんにちは、<?= htmlspecialchars($name) ?> さん</h1>
<h2>理想のChromebookの使い方を入力しましょう</h2>

<?php if ($message): ?>
    <p style="color:red;"><?= $message ?></p>
<?php endif; ?>

<div class="form-container">
<form method="post">

    <?php for ($i = 1; $i <= 3; $i++): ?>
        <fieldset>
            <legend><?= $i ?>つ目の理想の使い方（<?= $i === 1 ? '必須' : '任意' ?>）</legend>

            <label>どの時間の理想のクロムの使い方ですか？</label>
            <select name="time_category<?= $i ?>" <?= $i === 1 ? 'required' : '' ?>>
                <option value="">選択してください</option>
                <option value="休み時間">休み時間</option>
                <option value="授業の時間">授業の時間</option>
                <option value="家での時間">家での時間</option>
            </select>

            <label>どのように</label>
            <select name="how_use<?= $i ?>" <?= $i === 1 ? 'required' : '' ?>>
                <option value="">選択してください</option>
                <option value="学習に必要だと思った時に">学習に必要だと思った時に</option>
                <option value="振り返りの時に">振り返りの時に</option>
                <option value="好きなときに">好きなときに</option>
            </select>

            <label>なにを</label>
            <select name="what_use<?= $i ?>" <?= $i === 1 ? 'required' : '' ?>>
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
            <select name="want_do<?= $i ?>" <?= $i === 1 ? 'required' : '' ?>>
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
        </fieldset>
    <?php endfor; ?>

    <h3 style="margin-top:30px;">他の人の使い方で気になることや嫌だなと思ったこと</h3>
    <label>休み時間</label>
    <textarea name="free_rest" placeholder="自由に書いてください"></textarea>

    <label>授業中</label>
    <textarea name="free_class" placeholder="自由に書いてください"></textarea>

    <label>家での時間</label>
    <textarea name="free_home" placeholder="自由に書いてください"></textarea>

    <button type="submit">送信する</button>
</form>
</div>

</body>
</html>
