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

// CSRFトークン生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 削除処理
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $delete_id]);
    $_SESSION['ok'] = "ユーザーID {$delete_id} を削除しました。";
    header('Location: admin_register.php');
    exit;
}

// CSV出力処理
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="users.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'ログインID', '表示名', 'ロール', 'クラスID']);
    $stmt = $pdo->query("SELECT id, username, name, role, class_id FROM users ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
    fclose($output);
    exit;
}

// 編集更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id  = (int)$_POST['edit_id'];
    $name     = trim($_POST['edit_name']);
    $role     = $_POST['edit_role'];
    $class_id = $_POST['edit_class_id'] ?: null;
    $password = $_POST['edit_password'] ?? '';

    $params = [
        ':id' => $edit_id,
        ':name' => $name,
        ':role' => $role,
        ':class_id' => $class_id
    ];

    $sql = "UPDATE users SET name=:name, role=:role, class_id=:class_id";
    if ($password !== '') {
        $sql .= ", password=:password";
        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
    }
    $sql .= " WHERE id=:id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['ok'] = "ユーザー情報を更新しました。";
    header('Location: admin_register.php');
    exit;
}

// 検索
$search_query = '';
if (!empty($_GET['keyword'])) {
    $search_query = trim($_GET['keyword']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username LIKE :q OR name LIKE :q OR role LIKE :q ORDER BY id ASC");
    $stmt->execute([':q' => "%$search_query%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// クラス一覧取得
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// フラッシュメッセージ
$flash = function ($key) {
    if (!empty($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return '';
};
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>管理者：ユーザー管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:"Hiragino Kaku Gothic ProN","メイリオ",sans-serif;background:#f6f8fb;margin:0}
header{background:#0d5bd7;color:#fff;padding:16px;text-align:center;font-weight:700;position:relative}
.logout-btn{position:absolute;right:20px;top:16px;background:#fff;color:#0d5bd7;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-weight:700}
.container{max-width:900px;margin:24px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,.06)}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{border:1px solid #ccc;padding:8px;text-align:left}
th{background:#e8f0fe}
.btn{display:inline-block;background:#0d5bd7;color:#fff;border:none;border-radius:8px;padding:8px 12px;font-weight:700;cursor:pointer;text-decoration:none}
.delete-btn{background:#f44336}
.edit-btn{background:#ff9800}
form.inline{display:inline}
.msg-ok{background:#e8f5ff;color:#075aa0;border:1px solid #b8dcff;padding:10px;border-radius:8px;margin-bottom:12px}
.msg-err{background:#ffeeee;color:#a00;border:1px solid #ffc9c9;padding:10px;border-radius:8px;margin-bottom:12px}
.edit-form{background:#f9f9ff;padding:12px;border:1px solid #ccd;border-radius:8px;margin-top:8px}
</style>
</head>
<body>
<header>
管理者：ユーザー管理
<form method="get" action="admin_register.php" style="display:inline;">
    <button class="logout-btn" name="logout" value="1">ログアウト</button>
</form>
</header>

<div class="container">
    <h1>登録済みユーザー一覧</h1>
    <?php if ($msg = $flash('ok')): ?><div class="msg-ok"><?= $msg ?></div><?php endif; ?>
    <?php if ($msg = $flash('err')): ?><div class="msg-err"><?= $msg ?></div><?php endif; ?>

    <form method="get" action="admin_register.php" style="margin-bottom:12px;">
        <input type="text" name="keyword" value="<?= htmlspecialchars($search_query,ENT_QUOTES) ?>" placeholder="名前・ID・ロールで検索" style="width:60%;padding:8px;">
        <button class="btn">検索</button>
        <a href="admin_register.php" class="btn">リセット</a>
        <a href="admin_register.php?export=csv" class="btn">CSV出力</a>
    </form>

    <table>
        <tr><th>ID</th><th>ログインID</th><th>表示名</th><th>ロール</th><th>クラス</th><th>操作</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['username'],ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($u['name'],ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($u['role'],ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($u['class_id'] ?? '-',ENT_QUOTES) ?></td>
            <td>
                <form method="get" action="admin_register.php" class="inline">
                    <input type="hidden" name="edit" value="<?= $u['id'] ?>">
                    <button class="btn edit-btn" type="submit">編集</button>
                </form>
                <a href="admin_register.php?delete=<?= $u['id'] ?>" onclick="return confirm('削除してよろしいですか？');" class="btn delete-btn">削除</a>
            </td>
        </tr>
        <?php if (isset($_GET['edit']) && $_GET['edit'] == $u['id']): ?>
        <tr><td colspan="6">
            <form method="post" action="admin_register.php" class="edit-form">
                <input type="hidden" name="edit_id" value="<?= $u['id'] ?>">
                <label>表示名：</label>
                <input type="text" name="edit_name" value="<?= htmlspecialchars($u['name'],ENT_QUOTES) ?>" required><br>
                <label>ロール：</label>
                <select name="edit_role">
                    <option value="student" <?= $u['role']=='student'?'selected':'' ?>>生徒</option>
                    <option value="teacher" <?= $u['role']=='teacher'?'selected':'' ?>>先生</option>
                    <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>管理者</option>
                </select><br>
                <label>クラス：</label>
                <select name="edit_class_id">
                    <option value="">未設定</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $u['class_id']==$c['id']?'selected':'' ?>>
                            <?= htmlspecialchars($c['class_name'],ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>
                <label>パスワード変更（任意）：</label>
                <input type="password" name="edit_password" placeholder="変更しない場合は空欄"><br>
                <button class="btn" type="submit">保存</button>
            </form>
        </td></tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
