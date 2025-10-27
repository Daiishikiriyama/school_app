<?php
session_start();
require_once __DIR__ . '/db_connect.php';

// 管理者チェック
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'このページへは管理者のみアクセスできます。';
    exit;
}

// クラス一覧取得
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY grade DESC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

// CSV出力処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_type'], $_POST['class_id'])) {
    $exportType = $_POST['export_type'];
    $classId = (int)$_POST['class_id'];

    // クラス名を取得
    $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = :id");
    $stmt->execute([':id' => $classId]);
    $className = $stmt->fetchColumn() ?: '未設定クラス';

    // 出力対象別SQL
    if ($exportType === 'student') {
        $sql = "SELECT id, student_id, class_id, time_category, how_use, what_use, free_text, created_at 
                FROM student_entries WHERE class_id = :cid ORDER BY created_at ASC";
        $filename = "student_entries_{$className}_" . date('Ymd_His') . ".csv";
    } elseif ($exportType === 'teacher') {
        $sql = "SELECT id, teacher_id, class_id, time_category, content, created_at 
                FROM teacher_comments WHERE class_id = :cid ORDER BY created_at ASC";
        $filename = "teacher_comments_{$className}_" . date('Ymd_His') . ".csv";
    } else {
        exit('不正な出力タイプです。');
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cid' => $classId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 出力
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"" . mb_convert_encoding($filename, 'SJIS-win', 'UTF-8') . "\"");

    $output = fopen('php://output', 'w');
    if (!empty($rows)) {
        fputcsv($output, array_keys($rows[0]));
        foreach ($rows as $r) {
            // 改行・カンマ対策
            $escaped = array_map(fn($v) => str_replace(["\r", "\n"], [' ', ' '], $v), $r);
            fputcsv($output, $escaped);
        }
    } else {
        fputcsv($output, ['データがありません。']);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>管理者：データ出力</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {font-family:"Hiragino Kaku Gothic ProN","メイリオ",sans-serif;background:#f6f8fb;margin:0}
header {background:#0d5bd7;color:#fff;padding:16px;text-align:center;font-weight:700;position:relative}
nav {background:#003c9e;color:#fff;display:flex;justify-content:center;gap:20px;padding:10px 0;}
nav a {color:#fff;text-decoration:none;font-weight:bold;padding:6px 12px;border-radius:6px;}
nav a:hover {background:#0b57d0;}
.container {max-width:720px;margin:40px auto;background:#fff;padding:24px;border-radius:12px;
box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h1 {color:#0d5bd7;text-align:center;}
form {margin-top:24px;}
label {display:block;font-weight:bold;margin-top:16px;}
select,button {width:100%;padding:10px;margin-top:8px;border-radius:8px;border:1px solid #ccc;font-size:16px;}
button {background:#0d5bd7;color:#fff;font-weight:bold;cursor:pointer;}
button:hover {background:#003c9e;}
</style>
</head>
<body>
<header>
管理者：データ出力
</header>

<nav>
    <a href="admin_dashboard.php">📊 ダッシュボード</a>
    <a href="admin_register.php">👥 ユーザー管理</a>
    <a href="admin_classes.php">🏫 クラス管理</a>
    <a href="admin_export.php" style="background:#0b57d0;">⬇️ データ出力</a>
    <form method="get" action="admin_register.php" style="display:inline;">
        <button class="logout-btn" name="logout" value="1">ログアウト</button>
    </form>
</nav>

<div class="container">
<h1>クラス別データ出力</h1>
<p style="text-align:center;color:#555;">クラスと出力対象を選択して、CSVファイルをダウンロードできます。</p>

<form method="post" action="admin_export.php">
    <label>対象クラス：</label>
    <select name="class_id" required>
        <option value="">クラスを選択</option>
        <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'], ENT_QUOTES) ?></option>
        <?php endforeach; ?>
    </select>

    <label>出力対象：</label>
    <select name="export_type" required>
        <option value="">選択してください</option>
        <option value="student">子供の入力データ（student_entries）</option>
        <option value="teacher">先生の入力データ（teacher_comments）</option>
    </select>

    <button type="submit">CSVをダウンロード</button>
</form>
</div>
</body>
</html>
