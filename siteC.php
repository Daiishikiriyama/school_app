<?php
session_start();
require_once 'db_connect.php';

// ---------------------------------------------
// データ取得関数（上位3位）
// ---------------------------------------------
function getTop3($pdo, $column, $timeCategory) {
    $stmt = $pdo->prepare("
        SELECT $column, COUNT(*) AS cnt
        FROM children_data
        WHERE time_category = :time
          AND $column IS NOT NULL
          AND $column != ''
        GROUP BY $column
        ORDER BY cnt DESC
        LIMIT 3
    ");
    $stmt->execute([':time' => $timeCategory]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------------------------------------
// 教員コメント取得
// ---------------------------------------------
function getTeacherComment($pdo, $timeCategory) {
    $stmt = $pdo->prepare("
        SELECT content
        FROM teacher_data
        WHERE time_category = :time
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([':time' => $timeCategory]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['content'] : null;
}

// ---------------------------------------------
// 時間カテゴリ一覧
// ---------------------------------------------
$timeCategories = ["休み時間", "授業の時間", "家での時間"];

// ---------------------------------------------
// データまとめ
// ---------------------------------------------
$data = [];
foreach ($timeCategories as $time) {
    $data[$time] = [
        'how_use' => getTop3($pdo, 'how_use', $time),
        'what_use' => getTop3($pdo, 'what_use', $time),
        'teacher_comment' => getTeacherComment($pdo, $time)
    ];
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <link rel="stylesheet" href="style.css">
<meta charset="UTF-8">
<title>サイトC：みんなの理想のクロムの使い方</title>
<style>
body {
    font-family: "Hiragino Kaku Gothic ProN", "メイリオ", sans-serif;
    background-color: #f2f4f8;
    margin: 40px;
}
h1 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
}
.section {
    background: #fff;
    margin: 30px auto;
    padding: 25px 35px;
    border-radius: 12px;
    width: 600px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
h2 {
    color: #0078d7;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 5px;
}
h3 {
    margin-top: 20px;
    color: #34495e;
}
ol {
    padding-left: 25px;
}
li {
    margin: 4px 0;
    line-height: 1.8;
}
.rank-number {
    font-weight: bold;
    color: #0078d7;
}
.comment-box {
    background-color: #f9fafc;
    border-left: 4px solid #0078d7;
    padding: 10px 15px;
    margin-top: 15px;
    border-radius: 6px;
}
.no-data {
    color: #888;
    font-style: italic;
}
</style>
</head>
<body>

<h1>みんなの各時間ごとの理想のクロムの使い方は！？</h1>

<?php foreach ($data as $time => $values): ?>
<div class="section">
    <h2>【<?= htmlspecialchars($time) ?>】</h2>

    <h3>■ どのように使いたいか</h3>
    <?php if (count($values['how_use']) > 0): ?>
        <ol>
        <?php foreach ($values['how_use'] as $index => $row): ?>
            <li><span class="rank-number"><?= $index + 1 ?>位：</span>
                <?= htmlspecialchars($row['how_use']) ?>
            </li>
        <?php endforeach; ?>
        </ol>
    <?php else: ?>
        <p class="no-data">まだデータがありません。</p>
    <?php endif; ?>

    <h3>■ なにを使いたいか</h3>
    <?php if (count($values['what_use']) > 0): ?>
        <ol>
        <?php foreach ($values['what_use'] as $index => $row): ?>
            <li><span class="rank-number"><?= $index + 1 ?>位：</span>
                <?= htmlspecialchars($row['what_use']) ?>
            </li>
        <?php endforeach; ?>
        </ol>
    <?php else: ?>
        <p class="no-data">まだデータがありません。</p>
    <?php endif; ?>

    <h3>■ 先生からみんなへの「<?= htmlspecialchars($time) ?>」に関するクロムの使い方に対する思い</h3>
    <?php if ($values['teacher_comment']): ?>
        <div class="comment-box">
            <?= nl2br(htmlspecialchars($values['teacher_comment'])) ?>
        </div>
    <?php else: ?>
        <p class="no-data">先生からのコメントはまだありません。</p>
    <?php endif; ?>

</div>
<?php endforeach; ?>

</body>
</html>
