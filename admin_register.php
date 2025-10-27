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

// ----------------------
// クラス一覧取得
// ----------------------
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// 削除処理
// ----------------------
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $delete_id]);
    $_SESSION['ok'] = "ユーザーID {$delete_id} を削除しました。";
    header('Location: admin_register.php');
    exit;
}

// ----------------------
// CSV出力処理
// ----------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $keyword = $_GET['keyword'] ?? '';
    $class_filter = $_GET['class_filter'] ?? '';
    $params = [];
    $conditions = [];

    if ($keyword !== '') {
        $conditions[] = "(username LIKE :kw OR name LIKE :kw OR role LIKE :kw)";
        $params[':kw'] = "%$keyword%";
    }
    if ($class_filter !== '') {
        $conditions[] = "class_id = :cid";
        $params[':cid'] = (int)$class_filter;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql = "SELECT id, username, name, role, class_id FROM users $where ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="users_filtered.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'ログインID', '表示名', 'ロール', 'クラスID']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
    fclose($output);
    exit;
}

// ----------------------
// 編集保存処理
// ----------------------
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

// ----------------------
// 登録処理（改良版）
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit_id'])) {
    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $class_id = $_POST['class_id'] ?? '';

    $errors = [];
    $validRoles = ['admin', 'teacher', 'student'];

    if (!in_array($role, $validRoles, true)) $errors[] = 'ロールを選択してください。';
    if ($username === '' || !preg_match('/^[0-9]{4,}$/', $username))
        $errors[] = 'ログインIDは4桁以上の数字で入力してください。';
    if ($name === '' || mb_strlen($name) > 100)
        $errors[] = '表示名は1〜100文字で入力してください。';
    if (!preg_match('/^[0-9]{4,}$/', $password))
        $errors[] = 'パスワードは4桁以上の数字で入力してください。';
    if ($password !== $password2)
        $errors[] = 'パスワード（確認）が一致しません。';

    $classIdToSave = null;
    if ($role === 'student') {
        if ($class_id === '' || !ctype_digit($class_id))
            $errors[] = '生徒登録にはクラスの選択が必須です。';
        else
            $classIdToSave = (int)$class_id;
    } elseif ($role === 'teacher' && ctype_digit($class_id)) {
        $classIdToSave = (int)$class_id;
    }

    if (!$errors) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
        $check->execute([':u' => $username]);
        if ($check->fetchColumn() > 0) $errors[] = 'このログインIDはすでに使われています。';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role, class_id, name)
                VALUES (:username, :password, :role, :class_id, :name)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':password', $hash);
        $stmt->bindValue(':role', $role);
        if ($classIdToSave === null) $stmt->bindValue(':class_id', null, PDO::PARAM_NULL);
        else $stmt->bindValue(':class_id', $classIdToSave, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name);
        $stmt->execute();

        $_SESSION['ok'] = '✅ ユーザーを登録しました。';
        header('Location: admin_register.php');
        exit;
    } else {
        $_SESSION['err'] = implode("<br>", array_map(fn($m)=>htmlspecialchars($m,ENT_QUOTES),$errors));
        header('Location: admin_register.php');
        exit;
    }
}

// ----------------------
// 検索＋クラスフィルタ
// ----------------------
$keyword = $_GET['keyword'] ?? '';
$class_filter = $_GET['class_filter'] ?? '';
$params = [];
$conditions = [];

if ($keyword !== '') {
    $conditions[] = "(username LIKE :kw OR name LIKE :kw OR role LIKE :kw)";
    $params[':kw'] = "%$keyword%";
}
if ($class_filter !== '') {
    $conditions[] = "class_id = :cid";
    $params[':cid'] = (int)$class_filter;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "SELECT * FROM users $where ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// フラッシュメッセージ
$flash = fn($key) => $_SESSION[$key] ?? '';
unset($_SESSION['ok'], $_SESSION['err']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>管理者：ユーザー管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:"Hiragino Kaku Gothic ProN","メイリオ",sans-serif;background:#f6f8fb;margin:0}
header{background:#0d5bd7;color:#fff;padding:16px;text-align:center;font-weight:700;}
nav{background:#e8f0fe;padding:10px;text-align:center;font-weight:600;}
nav a{margin:0 10px;text-decoration:none;color:#0d5bd7;}
nav a.logout{color:red;}
.container{max-width:960px;margin:24px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,.06)}
.message{margin:10px 0;padding:10px;border-radius:8px}
.message.green{background:#e8f5e9;color:#2e7d32}
.message.red{background:#ffebee;color:#c62828}
.btn{display:inline-block;background:#0d5bd7;color:#fff;border:none;border-radius:8px;padding:8px 12px;font-weight:700;cursor:pointer;text-decoration:none}
.delete-btn{background:#f44336}
.edit-btn{background:#ff9800}
form.inline{display:inline}
.edit-form{background:#f9f9ff;padding:12px;border:1px solid #ccd;border-radius:8px;margin-top:8px}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{border:1px solid #ccc;padding:8px;text-align:left}
th{background:#e8f0fe}
</style>
</head>
<body>
<header>
管理者：ユーザー管理
</header>

<!-- ▼ 共通ナビゲーションメニュー -->
<nav style="background:#003c9e;color:#fff;display:flex;justify-content:center;gap:20px;padding:10px 0;">
    <a href="admin_dashboard.php" style="color:#fff;text-decoration:none;font-weight:bold;padding:6px 12px;border-radius:6px;">📊 ダッシュボード</a>
    <a href="admin_register.php" style="color:#fff;text-decoration:none;font-weight:bold;padding:6px 12px;border-radius:6px;background:#0b57d0;">👥 ユーザー管理</a>
    <a href="admin_classes.php" style="color:#fff;text-decoration:none;font-weight:bold;padding:6px 12px;border-radius:6px;">🏫 クラス管理</a>
    <a href="admin_export.php" style="color:#fff;text-decoration:none;font-weight:bold;padding:6px 12px;border-radius:6px;">⬇️ データ出力</a>
    <form method="get" action="admin_register.php" style="display:inline;margin-left:20px;">
        <button class="logout-btn" name="logout" value="1" 
                style="background:#fff;color:#0d5bd7;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;font-weight:700;">
            ログアウト
        </button>
    </form>
</nav>

<div class="container">

<h1>新規ユーザー登録</h1>
<?php if ($msg = $flash('ok')): ?><div class="message green"><?= $msg ?></div><?php endif; ?>
<?php if ($msg = $flash('err')): ?><div class="message red"><?= $msg ?></div><?php endif; ?>

<form method="post" action="admin_register.php" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
    <label>ロール：</label>
    <select name="role" required>
        <option value="">選択してください</option>
        <option value="student">生徒</option>
        <option value="teacher">先生</option>
        <option value="admin">管理者</option>
    </select>
    <label>クラス（任意 / 生徒は必須）</label>
    <select name="class_id">
        <option value="">クラスを選択</option>
        <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'], ENT_QUOTES) ?></option>
        <?php endforeach; ?>
    </select>
    <label>ログインID（4桁以上の数字）</label>
    <input type="text" name="username" required>
    <label>表示名</label>
    <input type="text" name="name" required>
    <label>パスワード（4桁以上の数字）</label>
    <input type="password" name="password" required>
    <label>パスワード（確認）</label>
    <input type="password" name="password2" required>
    <button class="btn" type="submit">登録する</button>
</form>

<h2 style="margin-top:40px;">登録済みユーザー一覧</h2>
<form method="get" action="admin_register.php" style="margin-bottom:12px;">
    <input type="text" name="keyword" value="<?= htmlspecialchars($keyword,ENT_QUOTES) ?>" placeholder="名前・ID・ロールで検索" style="width:40%;padding:8px;">
    <select name="class_filter" style="padding:8px;">
        <option value="">全クラス</option>
        <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($class_filter==$c['id'])?'selected':'' ?>>
                <?= htmlspecialchars($c['class_name'],ENT_QUOTES) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button class="btn">絞り込み</button>
    <a href="admin_register.php" class="btn">リセット</a>
    <a href="admin_register.php?export=csv&keyword=<?= urlencode($keyword) ?>&class_filter=<?= urlencode($class_filter) ?>" class="btn">CSV出力</a>
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
