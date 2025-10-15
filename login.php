<?php
session_start();
require_once 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username && $password) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // ✅ ハッシュ化 or 平文 どちらにも対応
                $is_valid = false;
                if (password_verify($password, $user['password']) || $user['password'] === $password) {
                    $is_valid = true;
                }

                if ($is_valid) {
                    $_SESSION['user_name'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // ロールによって遷移先を分岐
                    if ($user['role'] === 'student' || $user['role'] === 'child') {
                        header('Location: siteA.php');
                        exit;
                    } elseif ($user['role'] === 'teacher') {
                        header('Location: siteB.php');
                        exit;
                    } else {
                        $error = "不明なロールが設定されています。";
                    }
                } else {
                    $error = "パスワードが違います。";
                }
            } else {
                $error = "IDが存在しません。";
            }
        } catch (PDOException $e) {
            $error = "データベースエラー: " . $e->getMessage();
        }
    } else {
        $error = "すべての項目を入力してください。";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ログイン画面</title>
<style>
body {
    font-family: "Hiragino Kaku Gothic ProN", "メイリオ", sans-serif;
    background-color: #f4f6f8;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.login-box {
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    width: 350px;
}
h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 20px;
}
input {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 16px;
}
button {
    width: 100%;
    padding: 10px;
    background-color: #0078d7;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
button:hover {
    background-color: #005fa3;
}
.error {
    color: red;
    text-align: center;
    margin-bottom: 15px;
}
</style>
</head>
<body>
    <div class="login-box">
        <h2>ログイン</h2>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="username" placeholder="ログインID" required>
            <input type="password" name="password" placeholder="パスワード" required>
            <button type="submit">ログイン</button>
        </form>
    </div>
</body>
</html>
