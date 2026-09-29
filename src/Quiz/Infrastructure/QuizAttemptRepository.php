<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Infrastructure;

use Kaanbal\Quiz\Application\QuizScore;

final class QuizAttemptRepository
{
    private readonly \wpdb $database;

    public function __construct(?\wpdb $database = null)
    {
        global $wpdb;

        if (! $database instanceof \wpdb && ! $wpdb instanceof \wpdb) {
            throw new \RuntimeException('WordPress database access is required for quiz attempts.');
        }

        $this->database = $database ?? $wpdb;
    }

    public function hasPassed(int $user_id, int $quiz_id): bool
    {
        return null !== $this->database->get_var($this->database->prepare('SELECT id FROM ' . $this->tableName() . ' WHERE user_id = %d AND quiz_id = %d AND passed = 1 LIMIT 1', $user_id, $quiz_id));
    }

    public function countAttempts(int $user_id, int $quiz_id): int
    {
        return (int) $this->database->get_var($this->database->prepare('SELECT COUNT(*) FROM ' . $this->tableName() . ' WHERE user_id = %d AND quiz_id = %d', $user_id, $quiz_id));
    }

    /**
     * @param list<array{question_id: int, answer_id: int, is_correct: bool, question_text: string, answer_text: string}> $answers
     */
    public function record(int $user_id, int $course_id, int $quiz_id, ?int $max_attempts, QuizScore $score, array $answers): int
    {
        $lock_key = $this->acquireSequenceLock($user_id, $quiz_id);

        try {
            $this->begin();

            try {
                $latest = $this->database->get_var($this->database->prepare('SELECT attempt_number FROM ' . $this->tableName() . ' WHERE user_id = %d AND quiz_id = %d ORDER BY attempt_number DESC LIMIT 1 FOR UPDATE', $user_id, $quiz_id));
                $used = $this->countAttempts($user_id, $quiz_id);

                if ($this->hasPassed($user_id, $quiz_id)) {
                    throw new \DomainException('The quiz has already been passed.');
                }

                if (null !== $max_attempts && $used >= $max_attempts) {
                    throw new \DomainException('No quiz attempts remain.');
                }

                $now = current_time('mysql', true);
                $attempt_number = null === $latest ? 1 : ((int) $latest + 1);
                $result = $this->database->insert(
                    $this->tableName(),
                    array('user_id' => $user_id, 'course_id' => $course_id, 'quiz_id' => $quiz_id, 'attempt_number' => $attempt_number, 'status' => $score->passed ? 'passed' : 'failed', 'score' => $score->percentage, 'passed' => $score->passed ? 1 : 0, 'started_at' => $now, 'completed_at' => $now, 'created_at' => $now, 'updated_at' => $now)
                );

                if (false === $result) {
                    throw new \RuntimeException('The quiz attempt could not be saved.');
                }

                $attempt_id = (int) $this->database->insert_id;

                foreach ($answers as $answer) {
                    $saved = $this->database->insert(
                        $this->answersTableName(),
                        array('attempt_id' => $attempt_id, 'question_id' => $answer['question_id'], 'answer_id' => $answer['answer_id'], 'is_correct' => $answer['is_correct'] ? 1 : 0, 'question_text_snapshot' => $answer['question_text'], 'answer_text_snapshot' => $answer['answer_text'], 'created_at' => $now)
                    );

                    if (false === $saved) {
                        throw new \RuntimeException('The quiz answer could not be saved.');
                    }
                }

                $this->commit();

                return $attempt_id;
            } catch (\Throwable $exception) {
                $this->rollback();
                throw $exception;
            }
        } finally {
            $this->releaseSequenceLock($lock_key);
        }
    }

    private function acquireSequenceLock(int $user_id, int $quiz_id): string
    {
        $lock_key = sprintf('kaanbal_quiz_attempt_%d_%d', $user_id, $quiz_id);
        $acquired = $this->database->get_var($this->database->prepare('SELECT GET_LOCK(%s, 5)', $lock_key));

        if (1 !== (int) $acquired) {
            throw new \RuntimeException('The quiz attempt could not be locked for processing.');
        }

        return $lock_key;
    }

    private function releaseSequenceLock(string $lock_key): void
    {
        $this->database->get_var($this->database->prepare('SELECT RELEASE_LOCK(%s)', $lock_key));
    }

    private function begin(): void
    {
        if (false === $this->database->query('START TRANSACTION')) {
            throw new \RuntimeException('The quiz attempt transaction could not be started.');
        }
    }

    private function commit(): void
    {
        if (false === $this->database->query('COMMIT')) {
            throw new \RuntimeException('The quiz attempt transaction could not be committed.');
        }
    }

    private function rollback(): void
    {
        $this->database->query('ROLLBACK');
    }

    private function tableName(): string
    {
        return $this->database->prefix . 'kaanbal_quiz_attempts';
    }

    private function answersTableName(): string
    {
        return $this->database->prefix . 'kaanbal_quiz_attempt_answers';
    }
}
