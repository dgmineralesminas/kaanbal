<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Infrastructure;

final class AnswerRepository
{
    private readonly \wpdb $database;

    public function __construct(?\wpdb $database = null)
    {
        global $wpdb;

        if (! $database instanceof \wpdb && ! $wpdb instanceof \wpdb) {
            throw new \RuntimeException('WordPress database access is required for quiz answers.');
        }

        $this->database = $database ?? $wpdb;
    }

    /** @param list<int> $question_ids
     * @return array<int, list<array{id: int, question_id: int, answer_text: string, is_correct: bool}>>
     */
    public function forQuestions(array $question_ids): array
    {
        $question_ids = array_values(array_unique(array_filter(array_map('absint', $question_ids))));

        if (array() === $question_ids) {
            return array();
        }

        $placeholders = implode(', ', array_fill(0, count($question_ids), '%d'));
        $rows = $this->database->get_results($this->database->prepare('SELECT id, question_id, answer_text, is_correct FROM ' . $this->tableName() . ' WHERE question_id IN (' . $placeholders . ') ORDER BY position ASC, id ASC', ...$question_ids), 'ARRAY_A');
        $answers = array();

        foreach (is_array($rows) ? $rows : array() as $row) {
            $question_id = (int) $row['question_id'];
            $answers[$question_id][] = array('id' => (int) $row['id'], 'question_id' => $question_id, 'answer_text' => (string) $row['answer_text'], 'is_correct' => (bool) $row['is_correct']);
        }

        return $answers;
    }

    public function create(int $question_id, string $answer_text, bool $is_correct, int $position): void
    {
        $now = current_time('mysql', true);
        $result = $this->database->insert($this->tableName(), array('question_id' => $question_id, 'answer_text' => $answer_text, 'is_correct' => $is_correct ? 1 : 0, 'position' => $position, 'created_at' => $now, 'updated_at' => $now));

        if (false === $result) {
            throw new \RuntimeException('The answer could not be saved.');
        }
    }

    private function tableName(): string
    {
        return $this->database->prefix . 'kaanbal_question_answers';
    }
}
