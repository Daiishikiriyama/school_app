<?php
// admin_register.php
session_start();
require_once __DIR__ . '/db_connect.php';

// ✅ 管理者チェック
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'このページへは管理者のみアクセスできます。';
    exit;
}

// ✅ ログアウト処理
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ✅ CSRF トークン生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ ユーザー削除処理
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $delete_id]);
    $_SESSION['ok'] = "ユーザーID {$delete_id} を削除しました。";
    header('Location: admin_register.php');
    exit;
}

// ✅ CSV出力処理
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="users.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'ログインID', '表示名', 'ロール', 'クラスID']);

    $stmt = $pdo->query("SELECT id, username, name, role, class_id FROM users ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// ✅ 検索条件処理
$search_query = '';
$params = [];
if (!empty($_GET['keyword'])) {
    $search_query = trim($_GET['keyword']);
    $sql = "SELECT id, username, name, role, class_id 
            FROM users 
            WHERE username LIKE :q OR name LIKE :q OR role LIKE :q 
            ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':q' => "%$search_query%"]);
} else {
    $stmt = $pdo->query("SELECT id, username, name, role, class_id FROM users ORDER BY id ASC");
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ クラス一覧
$classes = [];
try {
    $stmt_class = $pdo->query("SELECT id, class_name FROM classes ORDER BY id ASC");
    $classes = $stmt_class->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classes = [];
}

// ✅ フラッシュメッセージ
$flash = function ($key) {
    if (!empty($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return '';
};

// ✅ POST処理（ユーザー登録）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['err'] = '不正なリクエストです。もう一度やり直してください。';
        header('Location: admin_register.php');
        exit;
    }

    $role      = $_POST['role']      ?? '';
    $username  = trim($_POST['username'] ?? '');
    $name      = trim($_POST['name']     ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';
    $class_id  = $_POST['class_id']  ?? '';

    $errors = [];
    $validRoles = ['admin', 'teacher', 'student'];
    if (!in_array($role, $validRoles, true)) $errors[] = 'ロールが不正です。';
    if ($username === '' || !preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $username))
        $errors[] = 'ログインIDは半角英数字・アンダーバー・ハイフンで3〜50文字にしてください。';
    if ($name === '' || mb_strlen($name) > 100)
        $errors[] = '表示名は1〜100文字で入力してください。';
    if (strlen($password) < 6)
        $errors[] = 'パスワードは6文字以上にしてください。';
    if ($password !== $password2)
        $errors[] = 'パスワード（確認）が一致しません。';

    // class_id チェック
    $classIdToSave = null;
    if ($role === 'student') {
        if ($class_id === '' || !ctype_digit($class_id))
            $errors[] = '生徒登録にはクラスの選択が必須です。';
        else
            $classIdToSave = (int)$class_id;
    } elseif ($role === 'teacher' && ctype_digit($class_id)) {
        $classIdToSave = (int)$class_id;
    }

    // 重複チェック
    if (!$errors) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
        $check->execute([':u' => $username]);
        if ($check->fetchColumn() > 0) $errors[] = 'このログインIDはすでに使われています。';
    }

    // 登録
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

        $_SESSION['ok'] = 'ユーザーを登録しました。';
        header('Location: admin_register.php');
        exit;
    } else {
        $_SESSION['err'] = implode("<br>", array_map(fn($m) => htmlspecialchars($m, ENT_QUOTES, 'UTF-8'), $errors));
        header('Location: admin_register.php');
        exit;
    }
}
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
.logout-btn:hover{background:#e8efff}
.container{max-width:900px;margin:24px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,.06)}
h1{font-size:22px;margin:0 0 16px}
label{display:block;font-weight:700;margin:14px 0 6px}
select,input[type=text],input[type=password]{width:100%;padding:10px;border:1px solid #cfd8ea;border-radius:8px;font-size:16px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:inline-block;background:#0d5bd7;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer;margin-top:18px}
.btn:hover{background:#0b49ad}
.msg-ok{background:#e8f5ff;color:#075aa0;border:1px solid #b8dcff;padding:10px 12px;border-radius:8px;margin-bottom:12px}
.msg-err{background:#ffeeee;color:#a00;border:1px solid #ffc9c9;padding:10px 12px;border-radius:8px;margin-bottom:12px}
small{color:#666}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{border:1px solid #ccc;padding:8px;text-align:left}
th{background:#e8f0fe}
.delete-btn{background:#f44336;color:#fff;border:none;padding:6px 10px;border-radius:6px;cursor:pointer}
.delete-btn:hover{background:#d32f2f}
.search-bar{margin-bottom:20px;display:flex;gap:10px}
.search-bar input{flex:1;padding:8px;border-radius:6px;border:1px solid #ccc}
.search-bar button{background:#0d5bd7;color:#fff;border:none;border-radius:6px;padding:8px 14px;cursor:pointer}
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
    <h1>ユーザー登録</h1>

    <?php if ($msg = $flash('ok')): ?>
        <div class="msg-ok"><?= $msg ?></div>
    <?php endif; ?>
    <?php if ($msg = $flash('err')): ?>
        <div class="msg-err"><?= $msg ?></div>
    <?php endif; ?>

    <form method="post" action="admin_register.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <label>ロール</label>
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
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['class_name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <div class="row">
            <div>
                <label>ログインID</label>
                <input type="text" name="username" required>
            </div>
            <div>
                <label>表示名</label>
                <input type="text" name="name" required>
            </div>
        </div>

        <div class="row">
            <div>
                <label>パスワード</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>パスワード（確認）</label>
                <input type="password" name="password2" required>
            </div>
        </div>

        <button class="btn" type="submit">登録する</button>
    </form>

    <h2 style="margin-top:40px;">登録済みユーザー一覧</h2>

    <div class="search-bar">
        <form method="get" action="admin_register.php" style="display:flex;width:100%;gap:10px;">
            <input type="text" name="keyword" placeholder="名前・ID・ロールで検索" value="<?= htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit">検索</button>
            <a href="admin_register.php" class="btn" style="text-decoration:none;">リセット</a>
            <a href="admin_register.php?export=csv" class="btn" style="text-decoration:none;">CSV出力</a>
        </form>
    </div>

    <table>
        <tr><th>ID</th><th>ログインID</th><th>表示名</th><th>ロール</th><th>クラス</th><th>操作</th></tr>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($u['class_id'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <a href="admin_register.php?delete=<?= (int)$u['id'] ?>" 
                       onclick="return confirm('このユーザーを削除してよろしいですか？');">
                       <button class="delete-btn">削除</button>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
