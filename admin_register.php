<?php
// admin_register.php
session_start();
require_once __DIR__ . '/db_connect.php';

// ✅ 管理者チェック（セッション変数名を login.php に合わせる）
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'このページへは管理者のみアクセスできます。';
    exit;
}

// ② CSRF トークン生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ③ クラス一覧を取得（セレクト用）
$classes = [];
try {
    $stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY id ASC");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // classes 未作成でも画面自体は出したい
    $classes = [];
}

// ④ フラッシュメッセージ
$flash = function ($key) {
    if (!empty($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return '';
};

// ⑤ POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF チェック
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['err'] = '不正なリクエストです。もう一度やり直してください。';
        header('Location: admin_register.php');
        exit;
    }

    // 入力値取得 & サニタイズ
    $role      = $_POST['role']      ?? '';
    $username  = trim($_POST['username'] ?? '');
    $name      = trim($_POST['name']     ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';
    $class_id  = $_POST['class_id']  ?? ''; // 文字列で来るのでのちに int 変換

    // バリデーション
    $errors = [];

    // role
    $validRoles = ['admin', 'teacher', 'student'];
    if (!in_array($role, $validRoles, true)) {
        $errors[] = 'ロールが不正です。';
    }

    // username
    if ($username === '' || !preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $username)) {
        $errors[] = 'ログインIDは半角英数字・アンダーバー・ハイフンで3〜50文字にしてください。';
    }

    // name
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = '表示名は1〜100文字で入力してください。';
    }

    // password
    if (strlen($password) < 6) {
        $errors[] = 'パスワードは6文字以上にしてください。';
    }
    if ($password !== $password2) {
        $errors[] = 'パスワード（確認）が一致しません。';
    }

    // class_id（student は必須 / teacher は任意 / admin は null）
    $classIdToSave = null;
    if ($role === 'student') {
        if ($class_id === '' || !ctype_digit($class_id)) {
            $errors[] = '生徒登録にはクラスの選択が必須です。';
        } else {
            $classIdToSave = (int)$class_id;
        }
    } elseif ($role === 'teacher') {
        if ($class_id !== '' && ctype_digit($class_id)) {
            $classIdToSave = (int)$class_id;
        } else {
            $classIdToSave = null; // 担任未設定でも可
        }
    } else { // admin
        $classIdToSave = null;
    }

    // 既存ユーザー重複チェック
    if (!$errors) {
        try {
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
            $check->execute([':u' => $username]);
            if ($check->fetchColumn() > 0) {
                $errors[] = 'このログインIDはすでに使われています。';
            }
        } catch (PDOException $e) {
            $errors[] = '重複チェック中にエラーが発生しました：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // 登録
    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (username, password, role, class_id, name)
                    VALUES (:username, :password, :role, :class_id, :name)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->bindValue(':password', $hash,     PDO::PARAM_STR);
            $stmt->bindValue(':role',     $role,     PDO::PARAM_STR);
            // class_id は null 許容
            if ($classIdToSave === null) {
                $stmt->bindValue(':class_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':class_id', $classIdToSave, PDO::PARAM_INT);
            }
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->execute();

            $_SESSION['ok'] = 'ユーザーを登録しました。';
            header('Location: admin_register.php');
            exit;
        } catch (PDOException $e) {
            $_SESSION['err'] = '登録に失敗しました：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            header('Location: admin_register.php');
            exit;
        }
    } else {
        $_SESSION['err'] = implode("<br>", array_map(function($m){
            return htmlspecialchars($m, ENT_QUOTES, 'UTF-8');
        }, $errors));
        header('Location: admin_register.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>管理者：ユーザー登録</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:"Hiragino Kaku Gothic ProN","メイリオ",sans-serif;background:#f6f8fb;margin:0}
header{background:#0d5bd7;color:#fff;padding:16px;text-align:center;font-weight:700}
.container{max-width:720px;margin:24px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,.06)}
h1{font-size:22px;margin:0 0 16px}
label{display:block;font-weight:700;margin:14px 0 6px}
select,input[type=text],input[type=password]{width:100%;padding:10px;border:1px solid #cfd8ea;border-radius:8px;font-size:16px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:inline-block;background:#0d5bd7;color:#fff;border:none;border-radius:10px;padding:12px 18px;font-weight:700;cursor:pointer;margin-top:18px}
.btn:hover{background:#0b49ad}
.msg-ok{background:#e8f5ff;color:#075aa0;border:1px solid #b8dcff;padding:10px 12px;border-radius:8px;margin-bottom:12px}
.msg-err{background:#ffeeee;color:#a00;border:1px solid #ffc9c9;padding:10px 12px;border-radius:8px;margin-bottom:12px}
small{color:#666}
</style>
</head>
<body>
<header>管理者：ユーザー登録</header>
<div class="container">
    <h1>新規ユーザーを登録</h1>

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
        <small>※ 生徒はクラス必須 / 先生は任意 / 管理者は不要</small>

        <label>クラス（任意 / 生徒は必須）</label>
        <select name="class_id">
            <option value="">クラスを選択</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['class_name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <div class="row">
            <div>
                <label>ログインID（半角英数字/3〜50文字）</label>
                <input type="text" name="username" required>
            </div>
            <div>
                <label>表示名（例：山田 太郎）</label>
                <input type="text" name="name" required>
            </div>
        </div>

        <div class="row">
            <div>
                <label>パスワード（6文字以上）</label>
                <input type="password" name="password" required>
            </div>
            <div>
                <label>パスワード（確認）</label>
                <input type="password" name="password2" required>
            </div>
        </div>

        <button class="btn" type="submit">登録する</button>
    </form>
</div>
</body>
</html>
