<?php
declare(strict_types=1);

ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');

session_start();


function require_login(): void {
  if (empty($_SESSION['user_id'])) {
    header("Location: /forms/client/login/login.php");
    exit;
  }
}

function require_login_json(): int {
  if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
  }
  return (int)$_SESSION['user_id'];
}

function hasFormAccess(int $formId): bool {
    return !empty($_SESSION['form_access'][$formId]);
}

function grantFormAccess(int $formId): void {
    $_SESSION['form_access'][$formId] = true;
}
