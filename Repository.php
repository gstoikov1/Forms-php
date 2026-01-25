<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Repository
{
    private function __construct()
    {
    }

    public static function getFormById(int $id): array|null
    {
        $pdo = db();

        $stmtForm = $pdo->prepare("SELECT id, name, owner_id, requires_code, code FROM forms WHERE id = ? LIMIT 1");
        $stmtForm->execute([$id]);
        $form = $stmtForm->fetch();
        if (!$form) return null;

        $stmtQuestions = $pdo->prepare("SELECT id, question_text, question_type, question_order FROM questions WHERE form_id = ? ORDER BY question_order ASC");
        $stmtQuestions->execute([$id]);
        $questions = $stmtQuestions->fetchAll();

        $stmtOptions = $pdo->prepare("SELECT id, option_text, option_order, question_id FROM question_options WHERE question_id = ? ORDER BY option_order ASC");

        $optionsByQuestionId = [];
        foreach ($questions as $q) {
            $stmtOptions->execute([(int)$q['id']]);
            $optionsByQuestionId[$q['id']] = $stmtOptions->fetchAll();
        }

        return [
            'form' => $form,
            'questions' => $questions,
            'questionOptions' => $optionsByQuestionId
        ];
    }

    public static function registerUser(string $username, string $pass, string $email): int
    {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        try {
            $pdo = db();
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);

            $stmt = $pdo->prepare("SELECT id
                                         FROM users
                                         WHERE username = ?");
            $stmt->execute([$username]);
            $row = $stmt->fetch();
            return $row ? (int)$row['id'] : -1;
        } catch (PDOException $e) {
            return -2;
        }
    }

    public static function loginUser(string $login, string $pass): mixed
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
        return ($user && password_verify($pass, $user['password_hash'])) ? $user : null;
    }

    public static function formExistsById(int $formId): bool
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id FROM forms WHERE id = ? LIMIT 1");
        $stmt->execute([$formId]);
        return $stmt->rowCount() > 0;
    }

    public static function saveFormSubmission(int $formId, array $mapQuestionIdToAnswer): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO forms_filled (form_id) VALUES (?)");
        $stmt->execute([$formId]);
        $stmtOpenQst = $pdo->prepare("INSERT INTO responses_open_questions
                                                   (form_filled_id, question_id, response_text)
                                            VALUES (?,?,?)");
        $stmtChoiceQst = $pdo->prepare("INSERT INTO responses_choice_questions
                                                    (form_filled_id, question_id, option_id) 
                                                    VALUES (?,?,?)");
        foreach ($mapQuestionIdToAnswer as $questionId => $data) {
            $type = $data['type'];
            if ($type == "OPEN") {
                $stmtOpenQst->execute([$formId, $questionId, $data['value']]);
            } elseif ($type == "SINGLE_CHOICE") {
                $stmtChoiceQst->execute([$formId, $questionId, $data['value']]);
            } elseif ($type == "MULTI_CHOICE") {
                foreach ($data['value'] as $optionId) {
                    $stmtChoiceQst->execute([$formId, $questionId,$optionId]);
                }
            }
        }
        $pdo->commit();
    }

    public static function getFormFilledCount(int $formId): int
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT count(*) FROM forms_filled WHERE form_id = ?");
        $stmt->execute([$formId]);

        return $stmt->fetchColumn();
    }

    public static function getQuestionAnswers($questionId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * from questions WHERE id = ?");
        $stmt->execute([$questionId]);
        if ($stmt->rowCount() === 0) {
            return [];
        }
        $question = $stmt->fetch();
        $type = $question['question_type'];
        $result = [];
        if ($type == "OPEN") {
            $result = self::getOpenQuestionAnswers($questionId);
        } elseif ($type == "SINGLE_CHOICE" or $type == "MULTI_CHOICE") {
            $result = self::getChoiceQuestionAnswers($questionId);
        }

        return $result;
    }

    private static function getOpenQuestionAnswers(int $questionId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT response_text FROM responses_open_questions WHERE question_id = ?");
        $stmt->execute([$questionId]);
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = $row['response_text'];
        }

        return $result;
    }

    private static function getChoiceQuestionAnswers($questionId): array
    {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, option_text, option_order FROM question_options WHERE question_id = ? ORDER BY id ASC");
        $stmt->execute([$questionId]);
        $result = [];
        while ($row = $stmt->fetch()) {
            $currentOptionId = $row['id'];
            $currentOptionChosenCountStmt = $pdo->prepare("SELECT count(*) FROM responses_choice_questions WHERE question_id = ? AND option_id = ?");
            $currentOptionChosenCountStmt->execute([$questionId, $currentOptionId]);
            $result[$row['option_order']] = [
                'text' => $row['option_text'],
                'responsesCount' => $currentOptionChosenCountStmt->fetchColumn()
            ];
        }

        return $result;
    }

}
