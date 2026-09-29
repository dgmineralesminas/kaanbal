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

    /**
     * Answer counts per active question, grouped by quiz, in one query.
     *
     * @param list<int> $quiz_ids
     * @return array<int, list<array{answers: int, correct: int}>>
     */
    public function answerStatsForQuizzes(array $quiz_ids): array
    {
        $quiz_ids = array_values(array_unique(array_filter(array_map('absint', $quiz_ids))));

        if (array() === $quiz_ids) {
            return array();
        }

        $placeholders = implode(', ', array_fill(0, count($quiz_ids), '%d'));
        $query = 'SELECT q.quiz_id, q.id, COUNT(a.id) AS answers, COALESCE(SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END), 0) AS correct'
            . ' FROM ' . $this->tableName() . ' q LEFT JOIN ' . $this->answersTableName() . ' a ON a.question_id = q.id'
            . ' WHERE q.active = 1 AND q.quiz_id IN (' . $placeholders . ') GROUP BY q.quiz_id, q.id';
        $rows = $this->database->get_results($this->database->prepare($query, ...$quiz_ids), 'ARRAY_A');
        $stats = array();

        foreach (is_array($rows) ? $rows : array() as $row) {
            $stats[(int) $row['quiz_id']][] = array('answers' => (int) $row['answers'], 'correct' => (int) $row['correct']);
        }

        return $stats;
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

    private function answersTableName(): string
    {
        return $this->database->prefix . 'kaanbal_question_answers';
    }
}
