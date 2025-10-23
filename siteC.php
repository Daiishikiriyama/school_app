<?php
session_start();
require_once 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------------------------------------------
// 集計関数（上位N件）
// ---------------------------------------------
function getTopN($pdo, $column, $timeCategory, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT $column AS item, COUNT(*) AS cnt
        FROM student_entries
        WHERE ($column IS NOT NULL AND $column != '')
          AND (
            time_category = :time OR
            time_category1 = :time OR
            time_category2 = :time OR
            time_category3 = :time
          )
        GROUP BY $column
        ORDER BY cnt DESC
        LIMIT $limit
    ");
    $stmt->execute([':time' => $timeCategory]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------------------------------------
// 「なに × したい」の組み合わせランキング
// ---------------------------------------------
function getCombinedTop($pdo, $timeCategory, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT CONCAT_WS(' × ', what_use, want_do) AS combo, COUNT(*) AS cnt
        FROM student_entries
        WHERE (what_use IS NOT NULL AND want_do IS NOT NULL)
          AND (
            time_category = :time OR
            time_category1 = :time OR
            time_category2 = :time OR
            time_category3 = :time
          )
        GROUP BY combo
        ORDER BY cnt DESC
        LIMIT $limit
    ");
    $stmt->execute([':time' => $timeCategory]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------------------------------------
// 教員コメント取得（各時間帯の最新1件）
// ---------------------------------------------
function getTeacherComment($pdo, $timeCategory) {
    $stmt = $pdo->prepare("
        SELECT comment_text
        FROM teacher_comments
        WHERE time_category = :time
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([':time' => $timeCategory]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['comment_text'] : null;
}

// ---------------------------------------------
// 時間カテゴリ一覧
// ---------------------------------------------
$timeCategories = ["休み時間", "授業の時間", "家での時間"];

// ---------------------------------------------
// 統計情報
// ---------------------------------------------
$total = 0;
$timeStats = [];
foreach ($timeCategories as $time) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM student_entries
        WHERE (
            time_category = :time OR
            time_category1 = :time OR
            time_category2 = :time OR
            time_category3 = :time
        )
    ");
    $stmt->execute([':time' => $time]);
    $count = $stmt->fetchColumn();
    $timeStats[$time] = $count;
    $total += $count;
}
$mostActive = array_search(max($timeStats), $timeStats);

// ---------------------------------------------
// データまとめ
// ---------------------------------------------
$data = [];
foreach ($timeCategories as $time) {
    $data[$time] = [
        'how_use' => getTopN($pdo, 'how_use', $time, 3),
        'what_do' => getCombinedTop($pdo, $time, 5),
        'teacher_comment' => getTeacherComment($pdo, $time)
    ];
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
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
    margin-bottom: 20px;
}
.stats {
    background: #fff;
    padding: 20px;
    width: 600px;
    margin: 0 auto 30px auto;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
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
ol { padding-left: 25px; }
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

<!-- 📊 概要情報 -->
<div class="stats">
    <h2>📊 概要・統計情報</h2>
    <p>全体入力件数：<?= $total ?>件</p>
    <ul>
        <?php foreach ($timeStats as $time => $cnt): ?>
            <li><?= htmlspecialchars($time) ?>：<?= $cnt ?>件</li>
        <?php endforeach; ?>
    </ul>
    <p><strong>最も多い時間帯：</strong><?= htmlspecialchars($mostActive) ?></p>
</div>

<!-- 🕒 各時間帯ごとのデータ -->
<?php foreach ($data as $time => $values): ?>
<div class="section">
    <h2>【<?= htmlspecialchars($time) ?>】</h2>

    <h3>■ どのようなタイミングで使いたいか</h3>
    <?php if (count($values['how_use']) > 0): ?>
        <ol>
        <?php foreach ($values['how_use'] as $index => $row): ?>
            <li><span class="rank-number"><?= $index + 1 ?>位：</span>
                <?= htmlspecialchars($row['item']) ?>（<?= $row['cnt'] ?>人）
            </li>
        <?php endforeach; ?>
        </ol>
    <?php else: ?>
        <p class="no-data">まだデータがありません。</p>
    <?php endif; ?>

    <h3>■ なにを × したいか</h3>
    <?php if (count($values['what_do']) > 0): ?>
        <ol>
        <?php foreach ($values['what_do'] as $index => $row): ?>
            <li><span class="rank-number"><?= $index + 1 ?>位：</span>
                <?= htmlspecialchars($row['combo']) ?>（<?= $row['cnt'] ?>人）
            </li>
        <?php endforeach; ?>
        </ol>
    <?php else: ?>
        <p class="no-data">まだデータがありません。</p>
    <?php endif; ?>

    <h3>■ 先生からみんなへの「<?= htmlspecialchars($time) ?>」に関する思い</h3>
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
