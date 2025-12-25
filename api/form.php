<?php
declare(strict_types=1);

require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../repository.php';

header('Content-Type: application/json');

$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($formId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid form id']);
  exit;
}

$data = Repository::getFormById($formId);

echo json_encode([
  'data' => $data,
  'session_test' => [
    'logged_in' => !empty($_SESSION['user_id']),
    'user_id'   => $_SESSION['user_id'] ?? null,
    'username'  => $_SESSION['username'] ?? null,
  ]
]);
