<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connect.php';

// --- 教員ログイン確認（仮設定） ---
if (!isset($_SESSION['user_name']) || $_SESSION['user_name'] !== 'teacher01') {
    $_SESSION['user_name'] = 'teacher01';
}
$teacher = trim($_SESSION['user_name']);

$message = "";

// ---------------------------------------------
// フォーム送信処理
// ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time_category = $_POST['time_category'] ?? '';
    $content = trim($_POST['content'] ?? '');

    if ($time_category && $content) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO teacher_data (user_id, time_category, content)
                VALUES (:user_id, :time_category, :content)
            ");
            $stmt->execute([
                ':user_id' => $teacher,
                ':time_category' => $time_category,
                ':content' => $content
            ]);
            $message = "登録しました！サイトCに反映されます。";
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
</head>
<body>

<header>
    理想のChromebook活用プロジェクト（教員ページ）
</header>

<h1 style="text-align:center; color:#2c3e50;">先生からみんなへの思いを入力</h1>

<div class="container">
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="post">
        <label>時間を選んでください</label>
        <select name="time_category" required>
            <option value="">選択してください</option>
            <option value="休み時間">休み時間</option>
            <option value="授業の時間">授業の時間</option>
            <option value="家での時間">家での時間</option>
        </select>

        <label>先生からクラスの子どもたちへのメッセージ</label>
        <textarea name="content" placeholder="例：休み時間は目を休める時間にもしてね。"></textarea>

        <button type="submit">登録する</button>
    </form>

    <!-- ▼ サイトCへの遷移ボタン -->
    <form action="siteC.php" method="get" style="margin-top: 10px;">
        <button type="submit" style="background-color: #1E88E5; color: white; border: none; border-radius: 6px; padding: 10px 20px; font-size: 16px; cursor: pointer; width: 100%; font-weight: bold;">
            サイトC（みんなの結果を見る）
        </button>
    </form>
</div>

<footer>
    常葉大学大学院 学校教育研究科 石切山大 ©
</footer>

</body>
</html>
