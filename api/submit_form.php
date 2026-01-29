<?php

require_once __DIR__ . '/../repository.php';

header('Content-Type: application/json');
// It's generally better to log errors than to suppress them entirely, 
// but keeping your original error_reporting(0) for consistency.
error_reporting(0);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$answers = $data['answers'] ?? [];
$formId = $data['form_id'] ?? -1;

if ($formId === -1 || !Repository::formExistsById($formId)) {
    http_response_code(400);
    echo json_encode(["error" => "Form does not exist"]);
    exit;
}

if (empty($answers)) {
    http_response_code(400);
    echo json_encode(["error" => "No answers given"]);
    exit;
}

$mapQuestionIdToAnswer = [];

foreach ($answers as $answer) {
    $qid = $answer['question_id'] ?? null;
    $type = $answer['type'] ?? null;
    
    $hasValue = array_key_exists('value', $answer) || 
                array_key_exists('option_id', $answer) || 
                array_key_exists('option_ids', $answer);

    if (!$qid || !$hasValue) {
        http_response_code(400);
        echo json_encode(["error" => "Question with id {$qid} does not have a valid answer structure"]);
        exit;
    }

    $value = $answer['value'] ?? $answer['option_id'] ?? $answer['option_ids'];

    $mapQuestionIdToAnswer[$qid] = [
        "type" => $type,
        "value" => $value
    ];
}

try {
    Repository::saveFormSubmission($formId, $mapQuestionIdToAnswer);
    echo json_encode([
        "status" => "success",
        "processed_answers" => $mapQuestionIdToAnswer
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Internal server error saving submission"]);
}