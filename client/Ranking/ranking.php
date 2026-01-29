<?php
require_once __DIR__ . '/../../session.php';
//require_login();

require_once __DIR__ . '/../../config.php'; 

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

$totalForms = 0;
$userCount = count($rows);

foreach ($rows as $row){
    $totalForms += (int)$row['forms_count'];
}

$averageForms = $userCount > 0  ? round($totalForms / $userCount, 2) : 0;

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

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ranking - Mockingbird Forms</title>
    <link rel="stylesheet" href="../index.css">
    <link rel="stylesheet" href="../button.css">
    <link rel="stylesheet" href="../dashboard/dashboard.css">
    <link rel="stylesheet" href="../ranking/ranking.css">
    <link rel="stylesheet" href="../pill.css"> 
    <link rel="stylesheet" href="../bird.css">
</head>
<body>

    <header class="main-header">
        <div style="display: flex; align-items: center; flex-grow: 1;">
            <a href="../dashboard/dashboard.php">
                <div class="mockingbird" style="transform: scaleX(-1); height: 49px;"></div>
            </a>
            <h1 class="project-title" style="margin-left: 15px;">User Ranking</h1>
        </div>
        <div class="header-right">
            <a href="../dashboard/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </header>

    <main class="ranking-container">

        <section class="form-card" style="width: 100%; max-width: 700px; box-sizing: border-box;">
            <h3 style="margin-top: 0; color: var(--color-main); border-bottom: 2px solid var(--color-highlight); padding-bottom: 10px;">
                🏆 Global Leaderboard
            </h3>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th style="text-align: right;">Forms Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rows as $row): ?>
                        <?php $isCurrent = ($row['username'] === $currentUser); ?>
                        <tr class="<?= $isCurrent ? 'current-user-row' : '' ?>">
                            <td>
                                <?= $isCurrent ? "<strong>" . htmlspecialchars($row['username']) . " (Me)</strong>" : htmlspecialchars($row['username']) ?>
                            </td>
                            <td style="text-align: right;">
                                <?= $isCurrent ? "<strong>" . (int)$row['forms_count'] . "</strong>" : (int)$row['forms_count'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="form-card" style="width: 100%; max-width: 700px; box-sizing: border-box;">
            <h3 style="margin-top: 0; color: var(--color-main);">Charts & Insights</h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Top users by volume of forms created.</p>
            
            <div class="histogram-content">
                <?php foreach($histogramUsers as $row):
                    $rank = array_search($row, $rows, true) + 1;
                    $barWidth = ($maxForms > 0) ? (int)(($row['forms_count'] / $maxForms) * 100) : 0;
                    $isCurrent = ($row['username'] === $currentUser);
                ?>
                    <div style="margin-bottom: 18px;">
                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                            <span>
                                <strong><?= $rank ?>.</strong> 
                                <?= $isCurrent ? "<b>" . htmlspecialchars($row['username']) . " (Me)</b>" : htmlspecialchars($row['username']) ?>
                            </span>
                            <strong><?= (int)$row['forms_count'] ?></strong>
                        </div>
                        <div class="bar-outer">
                            <div class="bar-inner" style="width: <?= $barWidth ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee; text-align: center;">
                <span style="padding: 8px 16px; border-radius: 20px; font-size: 14px;">
                    <strong>Average:</strong> <?= $averageForms ?> forms per user
                </span>
            </div>
        </section>

    </main>

    <footer class="main-footer" style="text-align: center; padding: 20px; color: #888; font-size: 14px;">
        <span>&copy; <?= date('Y') ?> Mockingbird Forms.</span>
        <span>Created by Veneta, Gabriel, Petar</span>
    </footer>

</body>
</html>