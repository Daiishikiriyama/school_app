<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

// ------------------------------
// ログイン確認
// ------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo "このページは教員のみがアクセスできます。";
    exit;
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['username'] ?? '先生';

// 担当クラス取得
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
// 教員メッセージ投稿処理
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time_category = $_POST['time_category'] ?? '';
    $comment_text = trim($_POST['comment_text'] ?? '');

    if ($time_category && $comment_text) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO teacher_comments (teacher_id, class_id, time_category, comment_text)
                VALUES (:teacher_id, :class_id, :time_category, :comment_text)
            ");
            $stmt->execute([
                ':teacher_id' => $teacher_id,
                ':class_id' => $class_id,
                ':time_category' => $time_category,
                ':comment_text' => $comment_text
            ]);
            $message = "「{$time_category}」へのメッセージを登録しました！";
        } catch (PDOException $e) {
            $message = "データベースエラー: " . $e->getMessage();
        }
    } else {
        $message = "すべての項目を入力してください。";
    }
}

// ------------------------------
// 教員メッセージ履歴取得
// ------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT time_category, comment_text, created_at 
        FROM teacher_comments 
        WHERE teacher_id = :teacher_id 
        ORDER BY created_at DESC
    ");
    $stmt->execute([':teacher_id' => $teacher_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "履歴取得エラー: " . $e->getMessage();
}

// ------------------------------
// 自分のクラスの生徒自由記述を取得
// ------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT u.name, s.free_rest, s.free_class, s.free_home
        FROM student_entries s
        JOIN users u ON s.user_id = u.id
        WHERE s.class_id = :class_id
        ORDER BY u.name ASC
    ");
    $stmt->execute([':class_id' => $class_id]);
    $student_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "生徒データ取得エラー: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>サイトB：教員用ページ</title>
<style>
body { font-family: "Yu Gothic UI", sans-serif; background-color: #f5f8fb; margin: 0; }
header { background: #1976D2; color: white; padding: 15px; text-align: center; font-size: 20px; }
.container { width: 80%; margin: 30px auto; background: white; padding: 25px; border-radius: 10px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
label { display: block; font-weight: bold; margin-top: 15px; }
select, textarea, button { width: 100%; padding: 10px; margin-top: 8px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; }
button { background-color: #1E88E5; color: white; border: none; cursor: pointer; font-weight: bold; }
button:hover { background-color: #1565C0; }
.message { color: green; font-weight: bold; margin-bottom: 10px; }
.comment-box { border-left: 4px solid #1976D2; background: #f1f5ff; padding: 10px 15px; margin-top: 10px; border-radius: 6px; }
.comment-time { font-size: 13px; color: #666; }
section h3 { background: #E3F2FD; padding: 8px; border-radius: 5px; }
.student-box { background: #fafafa; padding: 10px; margin-top: 10px; border-radius: 5px; border-left: 3px solid #42A5F5; }
footer { text-align: center; margin-top: 30px; color: #555; font-size: 13px; }
</style>
</head>
<body>

<header>理想のChromebook活用プロジェクト（教員ページ）</header>

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
        <textarea name="comment_text" rows="4" placeholder="例：授業中はお互いの考えを大切に聞きましょう。" required></textarea>

        <button type="submit">登録する</button>
    </form>

    <hr>

    <h3>これまでの登録メッセージ</h3>
    <?php if (!empty($comments)): ?>
        <?php foreach ($comments as $c): ?>
            <div class="comment-box">
                <div><strong><?= htmlspecialchars($c['time_category']) ?></strong></div>
                <div><?= nl2br(htmlspecialchars($c['comment_text'])) ?></div>
                <div class="comment-time"><?= htmlspecialchars($c['created_at']) ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>まだメッセージは登録されていません。</p>
    <?php endif; ?>

    <hr>

    <h3>クラスの子どもたちの自由記述一覧</h3>

    <section>
        <h3>🕹 休み時間</h3>
        <?php foreach ($student_entries as $s): if (!empty($s['free_rest'])): ?>
            <div class="student-box">
                <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                <?= nl2br(htmlspecialchars($s['free_rest'])) ?>
            </div>
        <?php endif; endforeach; ?>
    </section>

    <section>
        <h3>📖 授業の時間</h3>
        <?php foreach ($student_entries as $s): if (!empty($s['free_class'])): ?>
            <div class="student-box">
                <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                <?= nl2br(htmlspecialchars($s['free_class'])) ?>
            </div>
        <?php endif; endforeach; ?>
    </section>

    <section>
        <h3>🏠 家での時間</h3>
        <?php foreach ($student_entries as $s): if (!empty($s['free_home'])): ?>
            <div class="student-box">
                <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                <?= nl2br(htmlspecialchars($s['free_home'])) ?>
            </div>
        <?php endif; endforeach; ?>
    </section>

    <form action="siteC.php" method="get" style="margin-top: 15px;">
        <button type="submit" style="background-color: #388E3C;">サイトC（みんなの結果を見る）</button>
    </form>
</div>

<footer>常葉大学大学院 学校教育研究科 石切山大 ©</footer>
</body>
</html>
