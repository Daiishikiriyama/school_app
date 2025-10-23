<?php
session_start();
require_once(__DIR__ . '/config.php'); // 安全に設定ファイルを読み込む

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            $sql = "SELECT * FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // ✅ パスワード照合（平文 or ハッシュ両対応）
                $isPasswordValid = (
                    $password === $user['password'] || 
                    password_verify($password, $user['password'])
                );

                if ($isPasswordValid) {
                    // ✅ セッション情報をセット
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['class_id'] = $user['class_id']; // ★ここが重要！
                    $_SESSION['name'] = $user['name'] ?? '';

                    // ✅ ロール別のリダイレクト
                    if ($user['role'] === 'student') {
                        header("Location: siteA.php");
                        exit();
                    } elseif ($user['role'] === 'teacher') {
                        header("Location: siteB.php");
                        exit();
                    } elseif ($user['role'] === 'admin') {
                        header("Location: admin_register.php");
                        exit();
                    } else {
                        $error = "不明なロールが設定されています。";
                    }
                } else {
                    $error = "ユーザー名またはパスワードが間違っています。";
                }
            } else {
                $error = "ユーザーが存在しません。";
            }
        } catch (PDOException $e) {
            $error = "データベースエラー：" . $e->getMessage();
        }
    } else {
        $error = "すべてのフィールドを入力してください。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログインページ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>ログイン</h2>
        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="ユーザー名" required><br>
            <input type="password" name="password" placeholder="パスワード" required><br>
            <button type="submit">ログイン</button>
        </form>
    </div>
</body>
</html>
