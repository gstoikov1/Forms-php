<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';

header('Content-Type: application/json');

$userId = require_login_json();
$pdo = db();

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        requires_code,
        code
    FROM forms
    WHERE owner_id = ?
    ORDER BY id DESC
");

$stmt->execute([$userId]);
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'forms' => $forms
]);