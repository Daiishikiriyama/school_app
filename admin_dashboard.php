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

// ---------------------------------------------
// クラスごとのユーザー集計
// ---------------------------------------------
$sql = "
    SELECT 
        c.grade,
        c.class_name,
        SUM(CASE WHEN u.role = 'student' THEN 1 ELSE 0 END) AS student_count,
        SUM(CASE WHEN u.role = 'teacher' THEN 1 ELSE 0 END) AS teacher_count
    FROM classes c
    LEFT JOIN users u ON c.id = u.class_id
    GROUP BY c.id
    ORDER BY c.grade ASC, c.class_name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$classStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------
// 全体統計
// ---------------------------------------------
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>📊 管理者ダッシュボード</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:"Hiragino Kaku Gothic ProN","メイリオ",sans-serif;background:#f6f8fb;margin:0}
header{background:#0d5bd7;color:#fff;padding:16px;text-align:center;font-weight:700;}
nav{background:#e8f0fe;padding:10px;text-align:center;font-weight:600;}
nav a{margin:0 10px;text-decoration:none;color:#0d5bd7;}
nav a.logout{color:red;}
.container{max-width:1000px;margin:24px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 10px 20px rgba(0,0,0,.06)}
h1{color:#2c3e50;text-align:center;margin-bottom:30px}
.stat-box{display:flex;justify-content:space-around;text-align:center;margin-bottom:40px}
.stat{background:#e8f0fe;padding:16px;border-radius:12px;width:20%;box-shadow:0 3px 6px rgba(0,0,0,0.1)}
.stat h2{margin:0;font-size:18px;color:#0d5bd7}
.stat p{margin:8px 0 0;font-size:22px;font-weight:bold;color:#2c3e50}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ccc;padding:8px;text-align:center}
th{background:#e8f0fe}
tr:nth-child(even){background:#fafafa}
footer{text-align:center;color:#888;margin-top:40px}
</style>
</head>
<body>

<header>管理者：ダッシュボード</header>
<nav>
    <a href="admin_dashboard.php">📊 ダッシュボード</a> |
    <a href="admin_register.php">👥 ユーザー管理</a> |
    <a href="admin_classes.php">🏫 クラス管理</a> |
    <a href="login.php?logout=1" class="logout">🚪 ログアウト</a>
</nav>

<div class="container">
<h1>📊 登録状況ダッシュボード</h1>

<div class="stat-box">
    <div class="stat">
        <h2>全ユーザー数</h2>
        <p><?= (int)$totalUsers ?></p>
    </div>
    <div class="stat">
        <h2>生徒</h2>
        <p><?= (int)$totalStudents ?></p>
    </div>
    <div class="stat">
        <h2>先生</h2>
        <p><?= (int)$totalTeachers ?></p>
    </div>
    <div class="stat">
        <h2>管理者</h2>
        <p><?= (int)$totalAdmins ?></p>
    </div>
</div>

<h2>🧱 学年・クラス別 登録状況</h2>
<table>
<tr><th>学年</th><th>クラス</th><th>生徒人数</th><th>先生人数</th></tr>
<?php foreach ($classStats as $c): ?>
<tr>
    <td><?= htmlspecialchars($c['grade'], ENT_QUOTES) ?>年</td>
    <td><?= htmlspecialchars($c['class_name'], ENT_QUOTES) ?></td>
    <td><?= (int)$c['student_count'] ?></td>
    <td><?= (int)$c['teacher_count'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<footer>常葉大学大学院 学校教育研究科 石切山大 ©</footer>
</div>
</body>
</html>
