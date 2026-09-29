<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Infrastructure;

final class QuestionRepository
{
    private readonly \wpdb $database;

    public function __construct(?\wpdb $database = null)
    {
        global $wpdb;

        if (! $database instanceof \wpdb && ! $wpdb instanceof \wpdb) {
            throw new \RuntimeException('WordPress database access is required for quiz questions.');
        }

        $this->database = $database ?? $wpdb;
    }

    /** @return list<array{id: int, quiz_id: int, question_text: string, position: int}> */
    public function activeForQuiz(int $quiz_id): array
    {
        $rows = $this->database->get_results(
            $this->database->prepare('SELECT id, quiz_id, question_text, position FROM ' . $this->tableName() . ' WHERE quiz_id = %d AND active = 1 ORDER BY position ASC, id ASC', $quiz_id),
            'ARRAY_A'
        );

        return array_map(
            static fn (array $row): array => array('id' => (int) $row['id'], 'quiz_id' => (int) $row['quiz_id'], 'question_text' => (string) $row['question_text'], 'position' => (int) $row['position']),
            is_array($rows) ? $rows : array()
        );
    }

    public function create(int $quiz_id, string $question_text, int $position): int
    {
        $now = current_time('mysql', true);
        $result = $this->database->insert($this->tableName(), array('quiz_id' => $quiz_id, 'question_text' => $question_text, 'question_type' => 'single_choice', 'position' => $position, 'active' => 1, 'created_at' => $now, 'updated_at' => $now));

        if (false === $result) {
            throw new \RuntimeException('The question could not be saved.');
        }

        return (int) $this->database->insert_id;
    }

    private function tableName(): string
    {
        return $this->database->prefix . 'kaanbal_questions';
    }
}
