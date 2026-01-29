<?php


require_once __DIR__ . "/../FormUtils.php";


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($formId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid form id']);
    exit;
}

$data = Repository::getFormById($formId);

if (!$data) {
    http_response_code(404);
    exit(json_encode("Not Found"));
}

if ($_SESSION['user_id'] != $data['form']['owner_id']) {
    http_response_code(403);
    exit(json_encode("Forbidden"));
}

http_response_code(200);
$result = [];
$result["formId"] = $formId;
$result["name"] = $data["form"]["name"];
$result["filledCount"] = Repository::getFormFilledCount($formId);
$result["questionsFilled"] = FormUtils::buildJsonFromFormEntries($formId);

FormUtils::exportFormResults($result);

exit;



