<?php

use JetBrains\PhpStorm\NoReturn;

require_once __DIR__ . '/Repository.php';

class FormUtils{

    private function __construct()
    {
    }

    public static function buildJsonFromFormEntries(int $id) : array
    {
        $form = Repository::getFormById($id);

        if (empty($form)) {
            throw new Exception("Form with ID {$id} not found.");
        }

        $jsonResult = [];

        $questions = $form['questions'];
        foreach ($questions as $question ) {
            $questionId = $question['id'];
            $jsonResult[$question['question_order']] = [
                'text' => $question['question_text'],
                'type' => $question['question_type'],
                'givenAnswers' => Repository::getQuestionAnswers($questionId)
            ];
        }

        return $jsonResult;
    }

   #[NoReturn] public  static function exportFormResults(array $form): void
    {
        $isSequentialList = function ($arr) {
            if (!is_array($arr)) return false;
            $keys = array_keys($arr);
            return $keys === array_keys($keys);
        };

        $buildChartData = function (array $question) use ($isSequentialList) {
            $type = $question['type'] ?? 'OPEN';
            $given = $question['givenAnswers'] ?? [];
            $chart = [
                'type' => $type,
                'question' => $question['text'] ?? '',
                'labels' => [],
                'values' => [],
                'total' => 0,
                'rawAnswers' => $isSequentialList($given) ? array_values($given) : null,
            ];

            if ($type === 'OPEN') {
                $chart['total'] = is_array($given) ? count($given) : 0;
                return $chart;
            }

            $total = 0;
            foreach ($given as $k => $opt) {
                $count = (is_array($opt) && isset($opt['responsesCount'])) ? (int)$opt['responsesCount'] : 0;
                $label = is_array($opt) ? ($opt['text'] ?? (string)$k) : (string)$opt;
                $chart['labels'][] = (string)$label;
                $chart['values'][] = $count;
                $total += $count;
            }
            $chart['total'] = $total;
            return $chart;
        };

        $exportMeta = [
            'formId' => $form['formId'] ?? null,
            'name' => $form['name'] ?? null,
            'filledCount' => $form['filledCount'] ?? null,
            'exportedAt' => (new DateTime('now', new DateTimeZone('Europe/Sofia')))
                ->format('Y-m-d H:i:s'),
        ];

        $questionsChart = [];
        foreach ($form['questionsFilled'] ?? [] as $qid => $question) {
            $questionsChart[$qid] = $buildChartData($question);
        }

        $filename = preg_replace('/[^A-Za-z0-9 _-]/', '', ($form['name'] ?? 'form')) . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $out = fopen('php://output', 'w');

        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Form:', $exportMeta['name']]);
        fputcsv($out, ['Form ID:', $exportMeta['formId']]);
        fputcsv($out, ['Responses (filledCount):', $exportMeta['filledCount']]);
        fputcsv($out, ['Exported at (ISO):', $exportMeta['exportedAt']]);
        fputcsv($out, []); // blank row

        foreach ($form['questionsFilled'] ?? [] as $qid => $question) {
            $q = $question['text'] ?? "Question {$qid}";
            $type = $question['type'] ?? 'OPEN';
            fputcsv($out, ["Question #{$qid}", $q, "Type:", $type]);

            $chart = $questionsChart[$qid];

            if ($type === 'OPEN' || $isSequentialList($question['givenAnswers'] ?? [])) {
                fputcsv($out, ['Respondent #', 'Answer']);
                $answers = array_values($question['givenAnswers'] ?? []);
                foreach ($answers as $i => $ans) {
                    fputcsv($out, [$i + 1, (string)$ans]);
                }
            } else {
                fputcsv($out, ['Option', 'Responses', 'Percent']);
                $total = $form['filledCount'];
                $given = $question['givenAnswers'] ?? [];
                foreach ($given as $k => $opt) {
                    $label = is_array($opt) ? ($opt['text'] ?? (string)$k) : (string)$opt;
                    $count = is_array($opt) && isset($opt['responsesCount']) ? (int)$opt['responsesCount'] : 0;
                    $percent = $total > 0 ? round(($count / $total) * 100, 2) . '%' : '0%';
                    fputcsv($out, [$label, $count, $percent]);
                }
            }

            fputcsv($out, []);
        }

        fclose($out);
        exit;
    }


}