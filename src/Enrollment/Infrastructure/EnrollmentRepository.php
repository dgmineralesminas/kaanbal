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

    /** @return list<array{id: int, course_id: int, status: string, completed_at: string|null}> */
    public function forUser(int $user_id): array
    {
        if ($user_id <= 0) {
            return array();
        }

        $rows = $this->database->get_results(
            $this->database->prepare(
                'SELECT id, course_id, status, completed_at FROM ' . $this->tableName() . ' WHERE user_id = %d AND status IN (%s, %s) ORDER BY id ASC',
                $user_id,
                'active',
                'completed'
            ),
            'ARRAY_A'
        );

        return array_map(
            static fn (array $row): array => array(
                'id'           => (int) $row['id'],
                'course_id'    => (int) $row['course_id'],
                'status'       => (string) $row['status'],
                'completed_at' => null === $row['completed_at'] ? null : (string) $row['completed_at'],
            ),
            is_array($rows) ? $rows : array()
        );
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

    /**
     * @param list<int> $course_ids
     * @return array<int, array{total: int, active: int, completed: int, revoked: int}>
     */
    public function summariesForCourses(array $course_ids): array
    {
        $course_ids = array_values(array_unique(array_filter(array_map('absint', $course_ids))));

        if (array() === $course_ids) {
            return array();
        }

        $placeholders = implode(', ', array_fill(0, count($course_ids), '%d'));
        $query = 'SELECT course_id, COUNT(*) AS total, '
            . 'SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS active, '
            . 'SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS completed, '
            . 'SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) AS revoked '
            . 'FROM ' . $this->tableName() . ' WHERE course_id IN (' . $placeholders . ') GROUP BY course_id';
        $rows = $this->database->get_results(
            $this->database->prepare($query, 'active', 'completed', 'revoked', ...$course_ids),
            'ARRAY_A'
        );
        $summaries = array();

        foreach (is_array($rows) ? $rows : array() as $row) {
            $summaries[(int) $row['course_id']] = array(
                'total'     => (int) $row['total'],
                'active'    => (int) $row['active'],
                'completed' => (int) $row['completed'],
                'revoked'   => (int) $row['revoked'],
            );
        }

        return $summaries;
    }

    /**
     * @param list<int> $course_ids
     * @return array<int, list<int>>
     */
    public function activeUserIdsForCourses(array $course_ids): array
    {
        $course_ids = array_values(array_unique(array_filter(array_map('absint', $course_ids))));

        if (array() === $course_ids) {
            return array();
        }

        $placeholders = implode(', ', array_fill(0, count($course_ids), '%d'));
        $query = 'SELECT course_id, user_id FROM ' . $this->tableName() . ' WHERE status = %s AND course_id IN (' . $placeholders . ')';
        $rows = $this->database->get_results($this->database->prepare($query, 'active', ...$course_ids), 'ARRAY_A');
        $users_by_course = array();

        foreach (is_array($rows) ? $rows : array() as $row) {
            $users_by_course[(int) $row['course_id']][] = (int) $row['user_id'];
        }

        return $users_by_course;
    }

    /**
     * @return array{items: list<array{user_id: int, display_name: string|null, email: string|null, status: string, completed_at: string|null}>, total: int, page: int, total_pages: int}
     */
    public function studentsForCourse(int $course_id, int $page, int $per_page, string $status, string $search, string $quiz_filter = 'all', ?int $quiz_id = null): array
    {
        $course_id = absint($course_id);
        $page = max(1, $page);
        $per_page = max(1, min(100, $per_page));
        $conditions = array('e.course_id = %d');
        $arguments = array($course_id);

        if (in_array($status, array('active', 'completed', 'revoked'), true)) {
            $conditions[] = 'e.status = %s';
            $arguments[] = $status;
        }

        if ('' !== $search) {
            $like = '%' . $this->database->esc_like($search) . '%';
            $conditions[] = '(u.display_name LIKE %s OR u.user_email LIKE %s)';
            $arguments[] = $like;
            $arguments[] = $like;
        }

        $quiz_join = '';

        if (in_array($quiz_filter, array('passed', 'not_passed'), true) && null !== $quiz_id && $quiz_id > 0) {
            $quiz_join = ' LEFT JOIN (SELECT user_id, MAX(passed) AS passed FROM ' . $this->attemptsTableName() . ' WHERE quiz_id = %d GROUP BY user_id) qa ON qa.user_id = e.user_id';
            array_unshift($arguments, $quiz_id);
            $conditions[] = 'passed' === $quiz_filter ? 'qa.passed = 1' : 'COALESCE(qa.passed, 0) = 0';
        }

        $from = ' FROM ' . $this->tableName() . ' e LEFT JOIN ' . $this->database->users . ' u ON u.ID = e.user_id' . $quiz_join . ' WHERE ' . implode(' AND ', $conditions);
        $total = (int) $this->database->get_var($this->database->prepare('SELECT COUNT(*)' . $from, ...$arguments));
        $total_pages = max(1, (int) ceil($total / $per_page));
        $page = min($page, $total_pages);
        $offset = ($page - 1) * $per_page;
        $query = 'SELECT e.user_id, e.status, e.completed_at, u.display_name, u.user_email' . $from . ' ORDER BY CASE WHEN u.display_name IS NULL THEN 1 ELSE 0 END, u.display_name ASC, e.id ASC LIMIT %d OFFSET %d';
        $item_arguments = array_merge($arguments, array($per_page, $offset));
        $rows = $this->database->get_results($this->database->prepare($query, ...$item_arguments), 'ARRAY_A');
        $items = array();

        foreach (is_array($rows) ? $rows : array() as $row) {
            $items[] = array(
                'user_id'      => (int) $row['user_id'],
                'display_name' => null === $row['display_name'] ? null : (string) $row['display_name'],
                'email'        => null === $row['user_email'] ? null : (string) $row['user_email'],
                'status'       => (string) $row['status'],
                'completed_at' => null === $row['completed_at'] ? null : (string) $row['completed_at'],
            );
        }

        return array('items' => $items, 'total' => $total, 'page' => $page, 'total_pages' => $total_pages);
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

    private function attemptsTableName(): string
    {
        return $this->database->prefix . 'kaanbal_quiz_attempts';
    }
}
