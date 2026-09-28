<?php

declare(strict_types=1);

namespace Kaanbal\Enrollment\Infrastructure;

final class EnrollmentSourceRepository
{
    private readonly \wpdb $database;

    public function __construct(?\wpdb $database = null)
    {
        global $wpdb;

        if (! $database instanceof \wpdb && ! $wpdb instanceof \wpdb) {
            throw new \RuntimeException('WordPress database access is required for enrollment sources.');
        }

        $this->database = $database ?? $wpdb;
    }

    /**
     * Records a WooCommerce source, or re-grants it when it was revoked.
     *
     * Re-granting a revoked source moves granted_at to the new grant, so the row shows
     * that access was restored after created_at; the revocation itself remains visible
     * in the WooCommerce order status history.
     * MySQL evaluates the assignments left to right, so granted_at is decided before
     * revoked_at is cleared.
     */
    public function addWooCommerceSource(int $enrollment_id, int $order_id, int $product_id, int $order_item_id): bool
    {
        $now    = current_time('mysql', true);
        $result = $this->database->query(
            $this->database->prepare(
                'INSERT INTO ' . $this->tableName() . ' (enrollment_id, source_type, order_id, product_id, order_item_id, granted_at, created_at) VALUES (%d, %s, %d, %d, %d, %s, %s) ON DUPLICATE KEY UPDATE granted_at = IF(revoked_at IS NULL, granted_at, %s), revoked_at = NULL',
                $enrollment_id,
                'woocommerce',
                $order_id,
                $product_id,
                $order_item_id,
                $now,
                $now,
                $now
            )
        );

        if (false === $result) {
            throw new \RuntimeException('The WooCommerce enrollment source could not be saved.');
        }

        return 0 !== $result;
    }

    /** @return list<int> */
    public function revokeWooCommerceSourcesByOrder(int $order_id): array
    {
        $enrollment_ids = array_map(
            'intval',
            $this->database->get_col(
                $this->database->prepare(
                    'SELECT DISTINCT enrollment_id FROM ' . $this->tableName() . ' WHERE source_type = %s AND order_id = %d AND revoked_at IS NULL',
                    'woocommerce',
                    $order_id
                )
            )
        );

        if (array() === $enrollment_ids) {
            return array();
        }

        $result = $this->database->query(
            $this->database->prepare(
                'UPDATE ' . $this->tableName() . ' SET revoked_at = %s WHERE source_type = %s AND order_id = %d AND revoked_at IS NULL',
                current_time('mysql', true),
                'woocommerce',
                $order_id
            )
        );

        if (false === $result) {
            throw new \RuntimeException('The WooCommerce enrollment sources could not be revoked.');
        }

        return $enrollment_ids;
    }

    public function hasValidSources(int $enrollment_id): bool
    {
        return null !== $this->database->get_var(
            $this->database->prepare(
                'SELECT 1 FROM ' . $this->tableName() . ' WHERE enrollment_id = %d AND revoked_at IS NULL LIMIT 1',
                $enrollment_id
            )
        );
    }

    private function tableName(): string
    {
        return $this->database->prefix . 'kaanbal_enrollment_sources';
    }
}
