<?php
// login.php
// セッションCookieの設定（開発時は secure=false、実運用時は true に）
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,      // <-- 本番では true（HTTPS必須）
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {
    session_set_cookie_params(0, '/', '', false, true);
}
session_start();

require_once 'db.php';

/* CSRFトークン */
function generate_csrf_token(){
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token($token){
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/* 簡易レートリミット（セッションベース） */
$max_attempts = 5;
$lockout_time = 300; // 秒（5分）

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_login_attempt'] = 0;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ロックアウト判定
    if ($_SESSION['login_attempts'] >= $max_attempts && (time() - $_SESSION['last_login_attempt']) < $lockout_time) {
        $remaining = $lockout_time - (time() - $_SESSION['last_login_attempt']);
        $error = "試行回数が多すぎます。{$remaining}秒後に再度お試しください。";
    } else {
        // CSRFチェック
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            $error = "不正なリクエストです（CSRF）。";
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($username === '' || $password === '') {
                $error = "ログインIDとパスワードを入力してください。";
            } else {
                // ユーザー取得（プリペアド）
                $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :u LIMIT 1");
                $stmt->execute([':u' => $username]);
                $user = $stmt->fetch();

                // 共通のエラーメッセージ（ユーザー存在有無を漏らさない）
                $bad = "ログインIDまたはパスワードが正しくありません。";

                if ($user && password_verify($password, $user['password'])) {
                    // 成功時の処理
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // リセット
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['last_login_attempt'] = 0;

                    // ロールに応じてリダイレクト
                    if ($user['role'] === 'student') {
                        header('Location: siteA.php');
                        exit;
                    } else {
                        header('Location: siteB.php');
                        exit;
                    }
                } else {
                    // 失敗
                    $_SESSION['login_attempts'] += 1;
                    $_SESSION['last_login_attempt'] = time();
                    $error = $bad;
                }
            }
        }
    }
}

// ページ表示部分
$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>ログイン</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
/* シンプルなフォームCSS */
body { font-family: system-ui, -apple-system, "Hiragino Kaku Gothic ProN", "メイリオ", sans-serif; background:#f6f8fb; padding:40px; }
.container { max-width:420px; margin:0 auto; background:white; padding:24px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
h1 { margin-top:0; font-size:20px; }
label { display:block; margin:12px 0 6px; font-weight:600; }
input[type="text"], input[type="password"] { width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box; }
button { margin-top:14px; width:100%; padding:10px; border:0; border-radius:6px; background:#2b6cb0; color:white; font-weight:600; cursor:pointer; }
.error { color:#c92a2a; margin-bottom:8px; }
.note { font-size:12px; color:#666; margin-top:10px; }
</style>
</head>
<body>
<div class="container">
  <h1>ログイン</h1>
  <?php if ($error): ?>
    <div class="error"><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></div>
  <?php endif; ?>
  <form method="post" action="login.php" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
    <label for="username">ログインID</label>
    <input id="username" type="text" name="username" required maxlength="50" autofocus>

    <label for="password">パスワード</label>
    <input id="password" type="password" name="password" required>

    <button type="submit">ログイン</button>
  </form>

  <div class="note">開発環境では admin_create_user.php でアカウント作成できます。運用時はこのファイルを削除してください。</div>
</div>
</body>
</html>
