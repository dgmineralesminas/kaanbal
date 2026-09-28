<?php

declare(strict_types=1);

namespace Kaanbal\Enrollment\Infrastructure;

final class ProductCourseRepository
{
    private readonly \wpdb $database;

    public function __construct(?\wpdb $database = null)
    {
        global $wpdb;

        if (! $database instanceof \wpdb && ! $wpdb instanceof \wpdb) {
            throw new \RuntimeException('WordPress database access is required for product-course associations.');
        }

        $this->database = $database ?? $wpdb;
    }

    public function attachCourse(int $product_id, int $course_id): bool
    {
        $result = $this->database->query(
            $this->database->prepare(
                'INSERT IGNORE INTO ' . $this->tableName() . ' (product_id, course_id, created_at) VALUES (%d, %d, %s)',
                $product_id,
                $course_id,
                current_time('mysql', true)
            )
        );

        if (false === $result) {
            throw new \RuntimeException('The product-course association could not be saved.');
        }

        return 1 === $result;
    }

    public function detachCourse(int $product_id, int $course_id): void
    {
        $result = $this->database->query(
            $this->database->prepare(
                'DELETE FROM ' . $this->tableName() . ' WHERE product_id = %d AND course_id = %d',
                $product_id,
                $course_id
            )
        );

        if (false === $result) {
            throw new \RuntimeException('The product-course association could not be removed.');
        }
    }

    /** @return list<int> */
    public function findCoursesByProduct(int $product_id): array
    {
        return array_map(
            'intval',
            $this->database->get_col(
                $this->database->prepare(
                    'SELECT course_id FROM ' . $this->tableName() . ' WHERE product_id = %d ORDER BY course_id ASC',
                    $product_id
                )
            )
        );
    }

    /** @param list<int> $product_ids @return array<int, list<int>> */
    public function findCoursesByProducts(array $product_ids): array
    {
        $product_ids = array_values(array_unique(array_filter(array_map('absint', $product_ids))));

        if (array() === $product_ids) {
            return array();
        }

        $placeholders = implode(', ', array_fill(0, count($product_ids), '%d'));
        $query        = $this->database->prepare(
            'SELECT product_id, course_id FROM ' . $this->tableName() . " WHERE product_id IN ({$placeholders}) ORDER BY product_id ASC, course_id ASC",
            ...$product_ids
        );
        $rows         = $this->database->get_results($query, 'ARRAY_A');
        $courses      = array();

        foreach ($rows as $row) {
            $product_id = (int) $row['product_id'];
            $courses[$product_id][] = (int) $row['course_id'];
        }

        return $courses;
    }

    /** @return list<int> */
    public function findProductsByCourse(int $course_id): array
    {
        return array_map(
            'intval',
            $this->database->get_col(
                $this->database->prepare(
                    'SELECT product_id FROM ' . $this->tableName() . ' WHERE course_id = %d ORDER BY product_id ASC',
                    $course_id
                )
            )
        );
    }

    /**
     * Makes the product grant exactly the selected courses among the managed ones.
     *
     * Associations to courses outside $managed_course_ids are never removed, so a form
     * only detaches the courses it actually displayed.
     *
     * @param list<int> $course_ids         Courses that must be associated.
     * @param list<int> $managed_course_ids Courses the caller is allowed to detach.
     */
    public function syncCourses(int $product_id, array $course_ids, array $managed_course_ids): void
    {
        $course_ids         = array_values(array_unique(array_filter(array_map('absint', $course_ids))));
        $managed_course_ids = array_values(array_unique(array_filter(array_map('absint', $managed_course_ids))));
        $existing           = $this->findCoursesByProduct($product_id);

        foreach (array_intersect(array_diff($existing, $course_ids), $managed_course_ids) as $course_id) {
            $this->detachCourse($product_id, $course_id);
        }

        foreach (array_diff($course_ids, $existing) as $course_id) {
            $this->attachCourse($product_id, $course_id);
        }
    }

    private function tableName(): string
    {
        return $this->database->prefix . 'kaanbal_product_courses';
    }
}
