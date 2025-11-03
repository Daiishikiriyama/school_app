<?php
session_save_path("/tmp");
session_start();
require_once 'db_connect.php';

// 🔐 アクセス制限：生徒のみ
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'student'
) {
    echo "このページは生徒のみがアクセスできます。";
    exit;
}

$user_id = $_SESSION['user_id'];
$class_id = $_SESSION['class_id'] ?? null;
$name = $_SESSION['name'] ?? $_SESSION['username'] ?? "生徒";

// ⚙️ フォーム送信処理
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_entries 
            (
                user_id, class_id,
                time_category1, how_use1, what_use1, want_do1,
                time_category2, how_use2, what_use2, want_do2,
                time_category3, how_use3, what_use3, want_do3,
                free_rest, free_class, free_home
            )
            VALUES (
                :user_id, :class_id,
                :time_category1, :how_use1, :what_use1, :want_do1,
                :time_category2, :how_use2, :what_use2, :want_do2,
                :time_category3, :how_use3, :what_use3, :want_do3,
                :free_rest, :free_class, :free_home
            )
        ");

        $stmt->execute([
            ':user_id' => $user_id,
            ':class_id' => $class_id,
            ':time_category1' => $_POST['time_category1'] ?? null,
            ':how_use1' => $_POST['how_use1'] ?? null,
            ':what_use1' => $_POST['what_use1'] ?? null,
            ':want_do1' => $_POST['want_do1'] ?? null,
            ':time_category2' => $_POST['time_category2'] ?? null,
            ':how_use2' => $_POST['how_use2'] ?? null,
            ':what_use2' => $_POST['what_use2'] ?? null,
            ':want_do2' => $_POST['want_do2'] ?? null,
            ':time_category3' => $_POST['time_category3'] ?? null,
            ':how_use3' => $_POST['how_use3'] ?? null,
            ':what_use3' => $_POST['what_use3'] ?? null,
            ':want_do3' => $_POST['want_do3'] ?? null,
            ':free_rest' => $_POST['free_rest'] ?? null,
            ':free_class' => $_POST['free_class'] ?? null,
            ':free_home' => $_POST['free_home'] ?? null
        ]);

        header("Location: siteC.php?msg=success");
        exit();
    } catch (PDOException $e) {
        $message = "データベースエラー： " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
        h1 { color: #333; }
        .error { color: red; margin-bottom: 10px; }
        .form-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 700px; margin: auto; }
        select, textarea, button, a.btn-link { width: 100%; padding: 10px; margin-top: 10px; font-size: 16px; border-radius: 8px; border: 1px solid #ccc; text-align:center; }
        textarea { height: 80px; resize: vertical; }
        button { background-color: #007bff; color: white; font-weight: bold; border: none; cursor: pointer; margin-top: 20px; }
        button:hover { background-color: #0056b3; }
        .btn-link { display:inline-block; background-color:#28a745; color:white; text-decoration:none; border:none; font-weight:bold; cursor:pointer; margin-top:10px; }
        .btn-link:hover { background-color:#218838; }
        .section { margin-bottom: 30px; }
        .note { color: #777; font-size: 0.9em; }
    </style>
</head>
<body>

<h1>こんにちは、<?= htmlspecialchars($name) ?> さん</h1>
<h2>理想のChromebookの使い方を入力しましょう</h2>

<?php if ($message): ?>
    <p class="error"><?= $message ?></p>
<?php endif; ?>

<div class="form-container">
<form method="post">
    <?php for ($i = 1; $i <= 3; $i++): ?>
        <div class="section">
            <h3><?= $i ?>つ目の入力 <?= $i === 1 ? "(必須)" : "(任意)" ?></h3>

            <label>どの時間の理想のChromebookの使い方ですか？</label>
            <select name="time_category<?= $i ?>" <?= $i === 1 ? "required" : "" ?>>
                <option value="">選択してください</option>
                <option value="休み時間">休み時間</option>
                <option value="授業の時間">授業の時間</option>
                <option value="家での時間">家での時間</option>
            </select>

            <label>どのように</label>
            <select name="how_use<?= $i ?>" <?= $i === 1 ? "required" : "" ?>>
                <option value="">選択してください</option>
                <option value="学習に必要だと思った時に">学習に必要だと思った時に</option>
                <option value="振り返りの時に">振り返りの時に</option>
                <option value="好きなときに">好きなときに</option>
            </select>

            <label>なにを</label>
            <select name="what_use<?= $i ?>" <?= $i === 1 ? "required" : "" ?>>
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
            <select name="want_do<?= $i ?>" <?= $i === 1 ? "required" : "" ?>>
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
    <?php endfor; ?>

    <h3>自由記述欄</h3>
    <label>休み時間に他の人のクロムの使い方で気になることや嫌だなと思ったこと</label>
    <textarea name="free_rest"></textarea>

    <label>授業中に他の人のクロムの使い方で気になることや嫌だなと思ったこと</label>
    <textarea name="free_class"></textarea>

    <label>家での時間に他の人のクロムの使い方で気になることや嫌だなと思ったこと</label>
    <textarea name="free_home"></textarea>

    <button type="submit">送信する</button>

    <!-- ✅ サイトCへの導線ボタン -->
    <?php if (isset($_SESSION['class_id'])): ?>
        <a href="siteC.php?class_id=<?= $_SESSION['class_id'] ?>" class="btn-link">📊 自分のクラスのランキングを見る</a>
    <?php endif; ?>

</form>
</div>

</body>
</html>

