<?php
session_start();
require_once 'config.php';

// エラーメッセージ初期化
$error = "";

// フォームが送信された場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    try {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === $user['password']) {
            // ログイン成功
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // ロール別遷移処理
            if ($user['role'] === 'teacher') {
                header("Location: teacher_home.php");
                exit;
            } elseif ($user['role'] === 'student') {
                header("Location: student_home.php");
                exit;
            } elseif ($user['role'] === 'admin') {
                header("Location: admin_register.php");
                exit;
            } else {
                $error = "不明なロールが設定されています。";
            }
        } else {
            $error = "ユーザー名またはパスワードが違います。";
        }
    } catch (PDOException $e) {
        $error = "データベースエラー: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>ログイン</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="username" placeholder="ユーザー名" required><br>
        <input type="password" name="password" placeholder="パスワード" required><br>
        <button type="submit">ログイン</button>
    </form>
</body>
</html>
