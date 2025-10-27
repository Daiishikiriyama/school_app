<?php
session_start();
require_once __DIR__ . '/db_connect.php';

// 管理者チェック
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'このページへは管理者のみアクセスできます。';
    exit;
}

// ログアウト処理
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// クラス一覧取得
$classes = $pdo->query("SELECT id, class_name, grade FROM classes ORDER BY grade ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// クラス追加処理
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $grade = trim($_POST['grade'] ?? '');
    $class_name = trim($_POST['class_name'] ?? '');

    $errors = [];
    if ($grade === '' || !preg_match('/^[0-9]{1,2}$/', $grade)) {
        $errors[] = '学年は数字（例：4, 5, 6）で入力してください。';
    }
    if ($class_name === '') {
        $errors[] = 'クラス名を入力してください（例：1組）。';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO classes (grade, class_name) VALUES (:grade, :class_name)");
        $stmt->execute([':grade' => $grade, ':class_name' => $class_name]);
        $_SESSION['ok'] = "✅ {$grade}年{$class_name} を追加しました。";
        header('Location: admin_classes.php');
        exit;
    } else {
        $_SESSION['err'] = implode('<br>', $errors);
        header('Location: admin_classes.php');
        exit;
    }
}

// ----------------------
// クラス削除処理
// ----------------------
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = :id");
    $stmt->execute([':id' => $delete_id]);
    $_SESSION['ok'] = "🗑 クラスID {$delete_id} を削除しました。";
    header('Location: admin_classes.php');
    exit;
}

// ----------------------
// クラス編集処理
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $edit_grade = trim($_POST['edit_grade'] ?? '');
    $edit_name = trim($_POST['edit_class_name'] ?? '');

    if ($edit_grade === '' || $edit_name === '') {
        $_SESSION['err'] = '学年とクラス名の両方を入力してください。';
    } else {
        $stmt = $pdo->prepare("UPDATE classes SET grade = :grade, class_name = :name WHERE id = :id");
        $stmt->execute([':grade' => $edit_grade, ':name' => $edit_name, ':id' => $edit_id]);
        $_SESSION['ok'] = "✏️ クラス情報を更新しました。";
    }

    header('Location: admin_classes.php');
    exit;
}

// メッセージ処理
$flash = fn($key) => $_SESSION[$key] ?? '';
unset($_SESSION['ok'], $_SESSION['err']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>🏫 管理者：クラス管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:"Hiragino Kaku Gothic ProN","メイリオ",sans-serif;background:#f6f8fb;margin:0}
header{background:#0d5bd7;color:#fff;padding:16px;text-align:center;font-weight:700;}
nav{background:#e8f0fe;padding:10px;text-align:center;font-weight:600;}
nav a{margin:0 10px;text-decoration:none;color:#0d5bd7;}
nav a.logout{color:red;}
.container{max-width:900px;margin:24px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,.06)}
.message{margin:10px 0;padding:10px;border-radius:8px}
.message.green{background:#e8f5e9;color:#2e7d32}
.message.red{background:#ffebee;color:#c62828}
form{margin-bottom:30px}
input,select{padding:8px;border:1px solid #ccc;border-radius:6px}
.btn{background:#0d5bd7;color:white;border:none;border-radius:6px;padding:8px 12px;cursor:pointer}
.btn:hover{background:#0b4cad}
.delete-btn{background:#f44336}
.edit-btn{background:#ff9800}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{border:1px solid #ccc;padding:8px;text-align:left}
th{background:#e8f0fe}
.edit-form{background:#f9f9ff;padding:12px;border:1px solid #ccd;border-radius:8px;margin-top:8px}
</style>
</head>
<body>

<header>管理者：クラス管理</header>
<nav>
    <a href="admin_dashboard.php">📊 ダッシュボード</a> |
    <a href="admin_register.php">👥 ユーザー管理</a> |
    <a href="admin_classes.php">🏫 クラス管理</a> |
    <a href="login.php?logout=1" class="logout">🚪 ログアウト</a>
</nav>

<div class="container">
<h2>新しいクラスを追加</h2>

<?php if ($msg = $flash('ok')): ?><div class="message green"><?= $msg ?></div><?php endif; ?>
<?php if ($msg = $flash('err')): ?><div class="message red"><?= $msg ?></div><?php endif; ?>

<form method="post" action="admin_classes.php">
    <input type="hidden" name="add_class" value="1">
    <label>学年：</label>
    <input type="number" name="grade" min="1" max="6" required style="width:80px;">
    <label>クラス名：</label>
    <input type="text" name="class_name" placeholder="例：1組" required style="width:120px;">
    <button type="submit" class="btn">追加する</button>
</form>

<h2>登録済みクラス一覧</h2>
<table>
<tr><th>ID</th><th>学年</th><th>クラス名</th><th>操作</th></tr>
<?php foreach ($classes as $c): ?>
<tr>
<td><?= $c['id'] ?></td>
<td><?= htmlspecialchars($c['grade'], ENT_QUOTES) ?>年</td>
<td><?= htmlspecialchars($c['class_name'], ENT_QUOTES) ?></td>
<td>
    <form method="get" action="admin_classes.php" class="inline" style="display:inline;">
        <input type="hidden" name="edit" value="<?= $c['id'] ?>">
        <button class="btn edit-btn" type="submit">編集</button>
    </form>
    <a href="admin_classes.php?delete=<?= $c['id'] ?>" onclick="return confirm('削除してよろしいですか？');" class="btn delete-btn">削除</a>
</td>
</tr>

<?php if (isset($_GET['edit']) && $_GET['edit'] == $c['id']): ?>
<tr><td colspan="4">
<form method="post" action="admin_classes.php" class="edit-form">
    <input type="hidden" name="edit_id" value="<?= $c['id'] ?>">
    <label>学年：</label>
    <input type="number" name="edit_grade" value="<?= htmlspecialchars($c['grade'], ENT_QUOTES) ?>" min="1" max="6" required>
    <label>クラス名：</label>
    <input type="text" name="edit_class_name" value="<?= htmlspecialchars($c['class_name'], ENT_QUOTES) ?>" required>
    <button type="submit" class="btn">保存</button>
</form>
</td></tr>
<?php endif; ?>

<?php endforeach; ?>
</table>

</div>
</body>
</html>
