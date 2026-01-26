<?php
require_once __DIR__ . '/../../session.php';
//require_login();

require_once __DIR__ . '/../../config.php'; // тук да имаш PDO връзката

$pdo = db();

$stmt = $pdo->query("
    SELECT
        u.username,
        COUNT(f.id) AS forms_count
    FROM users u
    LEFT JOIN forms f ON f.owner_id = u.id
    GROUP BY u.id, u.username
    ORDER BY forms_count DESC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentUser = $_SESSION['username'] ?? null;
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ranking</title>
    <link rel="stylesheet" href="/forms/client/index.css">
    <link rel="stylesheet" href="/forms/client/button.css">
    <link rel="stylesheet" href="/forms/client/dashboard/dashboard.css">
    <link rel="stylesheet" href="/forms/client/ranking/ranking.css">
    <link rel="stylesheet" href="/forms/client/bird.css">
</head>
<body style="background-color: #f8f9fa;">

    <div class="dashboard-wrapper" style="height: 12vh">
        <header class="main-header">
            <a href = "/forms/client/dashboard/dashboard.php"><div class="mockingbird" style="transform: scaleX(-1); height: 49px; margin-right: 0; padding-right: 0"></div></a>
             <h1 class="project-title">User Ranking</h1>
             <div class="header-right">
                   <a href="/forms/client/dashboard/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
             </div>
        </header>
    </div>

<div style="background-color: #f8f9fa; align: center">
<table  border-width="2px" color="black">
    <tr>
        <th>Username</th>
        <th>Forms created</th>
    </tr>

    <?php foreach($rows as $row): ?>
            <?php $isCurrent = ($row['username'] === $currentUser); ?>
            <tr>
                <td>
                    <?php if ($isCurrent): ?>
                        <strong><?= htmlspecialchars($row['username']) ?> (Me)</strong>
                    <?php else: ?>
                        <?= htmlspecialchars($row['username']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isCurrent): ?>
                        <strong><?= (int)$row['forms_count'] ?></strong>
                    <?php else: ?>
                        <?= (int)$row['forms_count'] ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

</table>
</div>

<?php
$totalForms = 0;
$userCount = count($rows);

foreach ($rows as $row){
    $totalForms += (int)$row['forms_count'];
}

$averageForms = $userCount > 0  ? round($totalForms / $userCount, 2) : 0;

$currentUser = $_SESSION['username'] ?? null;

$currentUserRank = null;
foreach ($rows as $index => $row){
    if ($row['username'] === $currentUser){
        $currentUserRank = $index + 1;
        break;
    }
}

$top3 = array_slice($rows, 0, 3);

$histogramUsers = $top3;

if ($currentUser && $currentUserRank > 3) {
    $histogramUsers[] = $rows[$currentUserRank - 1];
}

$maxForms = 0;
foreach ($histogramUsers as $u){
    if($u['forms_count'] > $maxForms){
        $maxForms = $u['forms_count'];
    }
}
?>

<div class = "histogram-wrapper">

<h3>Top users by number of created forms</h3>

<?php foreach($histogramUsers as $index => $row):
    $rank = array_search($row, $rows, true) + 1;

    $barWidth = ($maxForms > 0) ? (int)(($row['forms_count'] / $maxForms) * 300) : 0;
    $isCurrent = ($row['username'] === $currentUser);
?>
    <div style="margin-bottom:6px;">
        <table class="table-class">
           <tr>
               <td class="td1">
                    <?= $rank ?>.
                </td>

                <td class="td2">
                    <?php if ($isCurrent): ?>
                        <b><?= htmlspecialchars($row['username']) ?> (Me)</b>
                    <?php else: ?>
                        <?= htmlspecialchars($row['username']) ?>
                    <?php endif; ?>
                </td>

                <td class="td-results" width=<?= $barWidth ?>px>
                    <div class="results"></div>
                </td>

                <td class="td3">
                    <?= (int)$row['forms_count'] ?>
                </td>
        </table>
    </div>
<?php endforeach; ?>
</div>

<p style="margin-top: 15px; font-size: 16px; text-align: center;">
    <b>Average:</b> <?= $averageForms ?> forms per user
</p>

</body>
</html>

