<?php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db_connect.php';
// admin_create_user.php
require_once 'db.php';

// 簡易フォームでユーザーを作るだけ。POSTで username, password, role(student|teacher)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = ($_POST['role'] === 'teacher') ? 'teacher' : 'student';

    if ($username === '' || $password === '') {
        $error = "ユーザー名とパスワードを入力してください。";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:u, :p, :r)");
        try {
            $stmt->execute([':u'=>$username, ':p'=>$hash, ':r'=>$role]);
            $success = "ユーザー作成しました。";
        } catch (PDOException $e) {
            $error = "作成に失敗しました（重複など）。";
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin: create user</title></head>
<body>
<h2>開発用ユーザー作成</h2>
<?php if(!empty($error)) echo "<p style='color:red;'>".htmlspecialchars($error)."</p>"; ?>
<?php if(!empty($success)) echo "<p style='color:green;'>".htmlspecialchars($success)."</p>"; ?>
<form method="post">
  <label>username: <input name="username" required></label><br>
  <label>password: <input name="password" type="password" required></label><br>
  <label>role:
    <select name="role">
      <option value="student">student</option>
      <option value="teacher">teacher</option>
    </select>
  </label><br>
  <button type="submit">作成</button>
</form>
</body>
</html>
