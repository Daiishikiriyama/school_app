<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php'; // DB接続（$pdo）

// ------------------------------
// ログイン確認
// ------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo "このページは教員のみがアクセスできます。";
    exit;
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['username'] ?? '先生';

// DBから担当クラスを取得
try {
    $stmt = $pdo->prepare("SELECT class_id FROM users WHERE id = :id");
    $stmt->execute([':id' => $teacher_id]);
    $class_id = $stmt->fetchColumn();

    if (!$class_id) {
        echo "担当クラスが設定されていません。管理者にお問い合わせください。";
        exit;
    }
} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
    exit;
}

$message = "";

// ------------------------------
// フォーム送信処理
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time_category = $_POST['time_category'] ?? '';
    $comment_text = trim($_POST['comment_text'] ?? '');

    if ($time_category && $comment_text) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO teacher_comments (teacher_id, class_id, comment_text, created_at)
                VALUES (:teacher_id, :class_id, :comment_text, NOW())
            ");
            $stmt->execute([
                ':teacher_id' => $teacher_id,
                ':class_id' => $class_id,
                ':comment_text' => $comment_text
            ]);

            $message = "登録しました！（{$time_category}）サイトCに反映されます。";
        } catch (PDOException $e) {
            $message = "データベースエラー: " . $e->getMessage();
        }
    } else {
        $message = "すべての項目を入力してください。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>サイトB：教員用コメント入力ページ</title>
<link rel="stylesheet" href="style.css">
<style>
body { font-family: "Yu Gothic UI", sans-serif; background-color: #f4f8fb; }
.container { width: 60%; margin: 30px auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
label { display: block; font-weight: bold; margin-top: 15px; }
select, textarea, button { width: 100%; margin-top: 8px; padding: 10px; font-size: 15px; border-radius: 5px; border: 1px solid #ccc; }
button { background-color: #1E88E5; color: white; border: none; cursor: pointer; font-weight: bold; }
button:hover { background-color: #1565C0; }
.message { color: green; font-weight: bold; }
footer { text-align: center; margin-top: 30px; color: #555; }
</style>
</head>
<body>

<header>
    <h2 style="text-align:center; background:#1976D2; color:white; padding:10px;">理想のChromebook活用プロジェクト（教員ページ）</h2>
</header>

<div class="container">
    <h3>こんにちは、<?= htmlspecialchars($teacher_name) ?> さん（クラスID：<?= htmlspecialchars($class_id) ?>）</h3>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>時間帯を選んでください</label>
        <select name="time_category" required>
            <option value="">選択してください</option>
            <option value="休み時間">休み時間</option>
            <option value="授業の時間">授業の時間</option>
            <option value="家での時間">家での時間</option>
        </select>

        <label>先生からクラスの子どもたちへのメッセージ</label>
        <textarea name="comment_text" rows="4" placeholder="例：休み時間はお友だちと話す時間にもしてね。" required></textarea>

        <button type="submit">登録する</button>
    </form>

    <form action="siteC.php" method="get" style="margin-top: 10px;">
        <button type="submit" style="background-color: #388E3C;">サイトC（みんなの結果を見る）</button>
    </form>
</div>

<footer>
    常葉大学大学院 学校教育研究科 石切山大 ©
</footer>

</body>
</html>
