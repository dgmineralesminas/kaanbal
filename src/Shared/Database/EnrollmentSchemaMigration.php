<?php

declare(strict_types=1);

namespace Kaanbal\Shared\Database;

final class EnrollmentSchemaMigration implements SchemaMigration
{
    public function install(): void
    {
        global $wpdb;

        if (! $wpdb instanceof \wpdb) {
            throw new \RuntimeException('WordPress database access is required to install the enrollment schema.');
        }

        $wordpress_root = defined('ABSPATH') ? (string) constant('ABSPATH') : '';

        if ('' === $wordpress_root) {
            throw new \RuntimeException('The WordPress root is required to install the enrollment schema.');
        }

        require_once rtrim($wordpress_root, '/\\') . '/wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $product_courses = $wpdb->prefix . 'kaanbal_product_courses';
        $enrollments     = $wpdb->prefix . 'kaanbal_enrollments';
        $sources         = $wpdb->prefix . 'kaanbal_enrollment_sources';
        $progress        = $wpdb->prefix . 'kaanbal_lesson_progress';

        dbDelta(
            "CREATE TABLE {$product_courses} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                product_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY product_id (product_id),
                KEY course_id (course_id),
                UNIQUE KEY product_course (product_id, course_id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$enrollments} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                status varchar(30) NOT NULL,
                enrolled_at datetime NOT NULL,
                completed_at datetime NULL,
                revoked_at datetime NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY course_id (course_id),
                UNIQUE KEY user_course (user_id, course_id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$sources} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                enrollment_id bigint(20) unsigned NOT NULL,
                source_type varchar(30) NOT NULL,
                order_id bigint(20) unsigned NULL,
                product_id bigint(20) unsigned NULL,
                order_item_id bigint(20) unsigned NULL,
                granted_at datetime NOT NULL,
                revoked_at datetime NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY enrollment_id (enrollment_id),
                KEY order_id (order_id),
                KEY source_validity (enrollment_id, revoked_at),
                UNIQUE KEY enrollment_source_item (enrollment_id, source_type, order_item_id, product_id)
            ) {$charset_collate};"
        );

        dbDelta(
            "CREATE TABLE {$progress} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                lesson_id bigint(20) unsigned NOT NULL,
                completed_at datetime NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY lesson_id (lesson_id),
                UNIQUE KEY user_lesson (user_id, lesson_id)
            ) {$charset_collate};"
        );

        // dbDelta() reports no errors, so confirm the tables exist before the schema
        // version is recorded.
        foreach (array($product_courses, $enrollments, $sources, $progress) as $table) {
            if ($table !== $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)))) {
                throw new \RuntimeException(esc_html(sprintf('The Kaanbal table %s could not be created.', $table)));
            }
        }
    }
}
