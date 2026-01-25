<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';

$userId = require_login_json();
$pdo = db();

$stmt = $pdo->query("
    SELECT
        f.id,
        f.name,
        f.requires_code,
        u.username AS owner
    FROM forms f
    JOIN users u ON u.id = f.owner_id
    ORDER BY f.id DESC
");

$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'forms' => $forms
]);
