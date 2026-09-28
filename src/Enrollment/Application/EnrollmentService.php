<?php

declare(strict_types=1);

namespace Kaanbal\Enrollment\Application;

use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Enrollment\Infrastructure\EnrollmentSourceRepository;

final class EnrollmentService
{
    private readonly \wpdb $database;

    public function __construct(
        private readonly EnrollmentRepository $enrollments,
        private readonly EnrollmentSourceRepository $sources,
        ?\wpdb $database = null,
    ) {
        global $wpdb;

        if (! $database instanceof \wpdb && ! $wpdb instanceof \wpdb) {
            throw new \RuntimeException('WordPress database access is required for enrollment changes.');
        }

        $this->database = $database ?? $wpdb;
    }

    public function grantWooCommerceCourse(int $user_id, int $course_id, int $order_id, int $product_id, int $order_item_id): void
    {
        $this->beginTransaction();

        try {
            $enrollment = $this->enrollments->findOrCreate($user_id, $course_id);
            $reactivate = 'revoked' === $enrollment['status'];
            $this->sources->addWooCommerceSource($enrollment['id'], $order_id, $product_id, $order_item_id);

            if ($reactivate) {
                $this->enrollments->activate($enrollment['id']);
            }

            $this->commitTransaction();
        } catch (\Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }

        if ($enrollment['created'] || $reactivate) {
            do_action('kaanbal_student_enrolled', $user_id, $course_id, $enrollment['id']);
        }
    }

    public function revokeWooCommerceOrder(int $order_id): void
    {
        $this->beginTransaction();

        try {
            $enrollment_ids = $this->sources->revokeWooCommerceSourcesByOrder($order_id);

            foreach ($enrollment_ids as $enrollment_id) {
                if (! $this->sources->hasValidSources($enrollment_id)) {
                    $this->enrollments->revoke($enrollment_id);
                }
            }

            $this->commitTransaction();
        } catch (\Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }
    }

    private function beginTransaction(): void
    {
        if (false === $this->database->query('START TRANSACTION')) {
            throw new \RuntimeException('The enrollment transaction could not be started.');
        }
    }

    private function commitTransaction(): void
    {
        if (false === $this->database->query('COMMIT')) {
            throw new \RuntimeException('The enrollment transaction could not be committed.');
        }
    }

    private function rollbackTransaction(): void
    {
        $this->database->query('ROLLBACK');
    }
}
