<?php

declare(strict_types=1);

namespace Kaanbal\Enrollment\Infrastructure;

use Kaanbal\Enrollment\Application\EnrollmentLookup;

final class EnrollmentRepository implements EnrollmentLookup
{
    private readonly \wpdb $database;

    public function __construct(?\wpdb $database = null)
    {
        global $wpdb;

        if (! $database instanceof \wpdb && ! $wpdb instanceof \wpdb) {
            throw new \RuntimeException('WordPress database access is required for enrollments.');
        }

        $this->database = $database ?? $wpdb;
    }

    /** @return array{id: int, status: string}|null */
    public function findByUserAndCourse(int $user_id, int $course_id): ?array
    {
        $row = $this->database->get_row(
            $this->database->prepare(
                'SELECT id, status FROM ' . $this->tableName() . ' WHERE user_id = %d AND course_id = %d',
                $user_id,
                $course_id
            ),
            'ARRAY_A'
        );

        if (! is_array($row)) {
            return null;
        }

        return array('id' => (int) $row['id'], 'status' => (string) $row['status']);
    }

    /** @return array{id: int, status: string, created: bool} */
    public function findOrCreate(int $user_id, int $course_id): array
    {
        $now    = current_time('mysql', true);
        $result = $this->database->query(
            $this->database->prepare(
                'INSERT IGNORE INTO ' . $this->tableName() . ' (user_id, course_id, status, enrolled_at, created_at, updated_at) VALUES (%d, %d, %s, %s, %s, %s)',
                $user_id,
                $course_id,
                'active',
                $now,
                $now,
                $now
            )
        );

        if (false === $result) {
            throw new \RuntimeException('The enrollment could not be created.');
        }

        $enrollment = $this->findByUserAndCourse($user_id, $course_id);

        if (! is_array($enrollment)) {
            throw new \RuntimeException('The enrollment could not be retrieved after creation.');
        }

        return array(
            'id'      => $enrollment['id'],
            'status'  => $enrollment['status'],
            'created' => 1 === $result,
        );
    }

    public function activate(int $enrollment_id): void
    {
        $this->updateStatus($enrollment_id, 'active', null);
    }

    public function revoke(int $enrollment_id): void
    {
        $this->updateStatus($enrollment_id, 'revoked', current_time('mysql', true));
    }

    public function completeByUserAndCourse(int $user_id, int $course_id): bool
    {
        $now = current_time('mysql', true);
        $result = $this->database->query(
            $this->database->prepare(
                'UPDATE ' . $this->tableName() . ' SET status = %s, completed_at = %s, updated_at = %s WHERE user_id = %d AND course_id = %d AND status = %s',
                'completed',
                $now,
                $now,
                $user_id,
                $course_id,
                'active'
            )
        );

        if (false === $result) {
            throw new \RuntimeException('The enrollment could not be completed.');
        }

        return 1 === $result;
    }

    private function updateStatus(int $enrollment_id, string $status, ?string $revoked_at): void
    {
        $result = $this->database->query(
            $this->database->prepare(
                'UPDATE ' . $this->tableName() . ' SET status = %s, revoked_at = %s, updated_at = %s WHERE id = %d',
                $status,
                $revoked_at,
                current_time('mysql', true),
                $enrollment_id
            )
        );

        if (false === $result) {
            throw new \RuntimeException('The enrollment status could not be updated.');
        }
    }

    private function tableName(): string
    {
        return $this->database->prefix . 'kaanbal_enrollments';
    }
}
