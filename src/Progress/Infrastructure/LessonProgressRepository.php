<?php

declare(strict_types=1);

namespace Kaanbal\Progress\Infrastructure;

use Kaanbal\Progress\Application\LessonProgressStore;

final class LessonProgressRepository implements LessonProgressStore
{
    private readonly \wpdb $database;

    public function __construct(?\wpdb $database = null)
    {
        global $wpdb;

        if (! $database instanceof \wpdb && ! $wpdb instanceof \wpdb) {
            throw new \RuntimeException('WordPress database access is required for lesson progress.');
        }

        $this->database = $database ?? $wpdb;
    }

    public function complete(int $user_id, int $lesson_id): bool
    {
        $now = current_time('mysql', true);
        $result = $this->database->query($this->database->prepare(
            'INSERT IGNORE INTO ' . $this->tableName() . ' (user_id, lesson_id, completed_at, created_at, updated_at) VALUES (%d, %d, %s, %s, %s)',
            $user_id,
            $lesson_id,
            $now,
            $now,
            $now,
        ));

        if (false === $result) {
            throw new \RuntimeException('The lesson completion could not be recorded.');
        }

        return 1 === $result;
    }

    public function findCompletedLessonIds(int $user_id, array $lesson_ids): array
    {
        $lesson_ids = array_values(array_unique(array_filter(array_map('absint', $lesson_ids))));

        if ($user_id <= 0 || array() === $lesson_ids) {
            return array();
        }

        $placeholders = implode(', ', array_fill(0, count($lesson_ids), '%d'));
        $query = 'SELECT lesson_id FROM ' . $this->tableName() . ' WHERE user_id = %d AND lesson_id IN (' . $placeholders . ')';
        $rows = $this->database->get_col($this->database->prepare($query, $user_id, ...$lesson_ids));

        return array_values(array_unique(array_map('intval', $rows)));
    }

    private function tableName(): string
    {
        return $this->database->prefix . 'kaanbal_lesson_progress';
    }
}
