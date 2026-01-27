<?php
require_once __DIR__ . '/../session.php';
require_login();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

$formId = (int)($body['form_id'] ?? 0);
$userId = $_SESSION['user_id'] ?? 0;

if ($formId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid form ID']);
    exit;
}

$pdo = db();

$stmt = $pdo->prepare("SELECT owner_id FROM forms WHERE id = ?");
$stmt->execute([$formId]);
$form = $stmt->fetch();

if (!$form) {
    http_response_code(404);
    echo json_encode(['error' => 'Form not found']);
    exit;
}

if ((int)$form['owner_id'] !== (int)$userId) {
    http_response_code(403);
    echo json_encode(['error' => 'You are not allowed to delete this form']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM forms WHERE id = ?");
$stmt->execute([$formId]);

echo json_encode(['ok' => true]);
