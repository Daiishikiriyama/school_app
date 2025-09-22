<?php
session_start();

// ログインチェック（任意で追加）
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// DB接続
$host = "localhost";
$dbname = "school_app";
$username = "root";   // ここは環境に合わせて変更
$password = "";       // ここも環境に合わせて変更

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("データベース接続失敗: " . htmlspecialchars($e->getMessage()));
}

// 子ども・教員のデータを集計（例：スコア合算）
$sql = "
    SELECT user_id, SUM(score) AS total_score
    FROM (
        SELECT user_id, score FROM children_data
        UNION ALL
        SELECT user_id, score FROM teacher_data
    ) AS combined
    GROUP BY user_id
    ORDER BY total_score DESC
    LIMIT 10
";
$stmt = $pdo->query($sql);
$ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ランキング - サイトC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #333; }
        table { border-collapse: collapse; width: 60%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #f4f4f4; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>

<body>
    <h2>ランキング (サイトC)</h2>
    <table>
        <tr>
            <th>順位</th>
            <th>ユーザーID</th>
            <th>合計スコア</th>
        </tr>
        <?php foreach ($ranking as $index => $row): ?>
            <tr>
                <td><?= htmlspecialchars($index + 1) ?>位</td>
                <td><?= htmlspecialchars($row['user_id']) ?></td>
                <td><?= htmlspecialchars($row['total_score']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
