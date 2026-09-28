<?php

declare(strict_types=1);

$wordpress_path = getenv('KAANBAL_WP_PATH');

if (! is_string($wordpress_path) || '' === $wordpress_path) {
    fwrite(STDERR, "KAANBAL_WP_PATH must point to the WordPress root.\n");
    exit(1);
}

require_once rtrim($wordpress_path, DIRECTORY_SEPARATOR) . '/wp-load.php';
require_once rtrim($wordpress_path, DIRECTORY_SEPARATOR) . '/wp-admin/includes/user.php';

if (! class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce must be active for this integration test.\n");
    exit(1);
}

require_once dirname(__DIR__, 2) . '/kaanbal.php';

use Kaanbal\Bootstrap\Activator;
use Kaanbal\Enrollment\Infrastructure\ProductCourseRepository;
use Kaanbal\WooCommerce\WooCommerceModule;
use Kaanbal\WooCommerce\Presentation\Admin\ProductCourseMetaBox;

$customer_id = null;
$course_id   = null;
$product_ids = array();
$order_ids   = array();
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The fixture preserves the request payload before testing authorized and unauthorized saves.
$previous_post = $_POST;
$previous_user_id = get_current_user_id();

try {
    Activator::activate();
    do_action('init');

    $module = new WooCommerceModule();
    $module->register();
    do_action('plugins_loaded');

    $fixture_suffix = substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
    $customer       = wp_insert_user(
        array(
            'user_login' => 'kaanbal-enrollment-' . $fixture_suffix,
            'user_email' => 'kaanbal-enrollment-' . $fixture_suffix . '@example.test',
            'user_pass'  => wp_generate_password(),
            'role'       => 'subscriber',
        )
    );

    if (is_wp_error($customer)) {
        throw new RuntimeException('The WooCommerce enrollment customer fixture could not be created.');
    }

    $customer_id = (int) $customer;
    $course_id   = wp_insert_post(array('post_type' => 'kaanbal_course', 'post_title' => 'WooCommerce enrollment course', 'post_status' => 'publish'));
    $product_ids = array(
        wp_insert_post(array('post_type' => 'product', 'post_title' => 'WooCommerce enrollment product A', 'post_status' => 'publish')),
        wp_insert_post(array('post_type' => 'product', 'post_title' => 'WooCommerce enrollment product B', 'post_status' => 'publish')),
    );

    if (0 === $course_id || in_array(0, $product_ids, true)) {
        throw new RuntimeException('The WooCommerce enrollment fixture could not be created.');
    }

    foreach ($product_ids as $product_id) {
        wp_set_object_terms($product_id, 'simple', 'product_type');
        update_post_meta($product_id, '_regular_price', '100');
        update_post_meta($product_id, '_price', '100');
    }

    $product_courses = new ProductCourseRepository();
    $product_courses->attachCourse($product_ids[0], $course_id);

    $administrator_ids = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ids'));

    if (! isset($administrator_ids[0])) {
        throw new RuntimeException('An administrator is required for the product-course admin integration test.');
    }

    $meta_box = new ProductCourseMetaBox($product_courses);
    wp_set_current_user(0);
    $_POST = array(
        'kaanbal_product_courses_nonce' => 'invalid',
        'kaanbal_course_ids'            => array((string) $course_id),
    );
    $meta_box->save($product_ids[1]);

    if (array() !== $product_courses->findCoursesByProduct($product_ids[1])) {
        throw new RuntimeException('Product-course associations were saved without a valid nonce and capability.');
    }

    wp_set_current_user((int) $administrator_ids[0]);
    $_POST = array(
        'kaanbal_product_courses_nonce' => wp_create_nonce('kaanbal_save_product_courses'),
        'kaanbal_course_ids'            => array('999999999'),
    );
    $meta_box->save($product_ids[1]);

    if (array() !== $product_courses->findCoursesByProduct($product_ids[1])) {
        throw new RuntimeException('An invalid course association was saved.');
    }

    $_POST = array(
        'kaanbal_product_courses_nonce' => wp_create_nonce('kaanbal_save_product_courses'),
        'kaanbal_course_ids'            => array((string) $course_id),
    );
    $meta_box->save($product_ids[1]);

    if (array($course_id) !== $product_courses->findCoursesByProduct($product_ids[1])) {
        throw new RuntimeException('Product-course associations were not saved for an authorized product editor.');
    }

    $meta_box->save($course_id);

    if (array() !== $product_courses->findCoursesByProduct($course_id)) {
        throw new RuntimeException('An association was saved for a non-product post.');
    }
    $product_courses->attachCourse($product_ids[1], $course_id);

    $product_a = wc_get_product($product_ids[0]);
    $product_b = wc_get_product($product_ids[1]);

    if (! $product_a instanceof WC_Product || ! $product_b instanceof WC_Product) {
        throw new RuntimeException('The WooCommerce product fixtures could not be loaded.');
    }

    $order = wc_create_order(array('customer_id' => $customer_id));
    $order->add_product($product_a, 1);
    $order->calculate_totals();
    $order->save();
    $order_id = $order->get_id();
    $order_ids[] = $order_id;

    $order->update_status('processing');

    assertEnrollmentState($customer_id, $course_id, 1, 'active');
    $order->update_status('completed');
    assertEnrollmentState($customer_id, $course_id, 1, 'active');

    $alternative_order = wc_create_order(array('customer_id' => $customer_id));
    $alternative_order->add_product($product_b, 1);
    $alternative_order->calculate_totals();
    $alternative_order->save();
    $order_ids[] = $alternative_order->get_id();
    $alternative_order->update_status('processing');
    assertEnrollmentState($customer_id, $course_id, 2, 'active');

    $partial_refund = wc_create_refund(
        array(
            'amount'         => 10,
            'order_id'       => $order_id,
            'refund_payment' => false,
            'restock_items'  => false,
        )
    );

    if (is_wp_error($partial_refund)) {
        throw new RuntimeException('The partial refund fixture could not be created.');
    }

    assertEnrollmentState($customer_id, $course_id, 2, 'active');

    $order->update_status('refunded');
    assertEnrollmentState($customer_id, $course_id, 2, 'active');
    $alternative_order->update_status('refunded');
    assertEnrollmentState($customer_id, $course_id, 2, 'revoked');

    $reactivation_order = wc_create_order(array('customer_id' => $customer_id));
    $reactivation_order->add_product($product_a, 1);
    $reactivation_order->calculate_totals();
    $reactivation_order->save();
    $order_ids[] = $reactivation_order->get_id();
    $reactivation_order->update_status('processing');

    assertEnrollmentState($customer_id, $course_id, 3, 'active');

    $reactivation_order->update_status('cancelled');
    assertEnrollmentState($customer_id, $course_id, 3, 'revoked');

    $final_order = wc_create_order(array('customer_id' => $customer_id));
    $final_order->add_product($product_a, 1);
    $final_order->calculate_totals();
    $final_order->save();
    $order_ids[] = $final_order->get_id();
    $final_order->update_status('processing');
    assertEnrollmentState($customer_id, $course_id, 4, 'active');

    $guest_order = wc_create_order();
    $guest_order->add_product($product_a, 1);
    $guest_order->calculate_totals();
    $guest_order->save();
    $order_ids[] = $guest_order->get_id();
    $guest_order->update_status('processing');

    assertEnrollmentState($customer_id, $course_id, 4, 'active');

    echo "WooCommerce enrollment integration: PASS\n";
} finally {
    global $wpdb;

    $_POST = $previous_post;
    wp_set_current_user($previous_user_id);

    if ($wpdb instanceof wpdb && is_int($customer_id) && is_int($course_id)) {
        $sources     = $wpdb->prefix . 'kaanbal_enrollment_sources';
        $enrollments = $wpdb->prefix . 'kaanbal_enrollments';
        $enrollment_id = $wpdb->get_var($wpdb->prepare('SELECT id FROM %i WHERE user_id = %d AND course_id = %d', $enrollments, $customer_id, $course_id));

        if (is_numeric($enrollment_id)) {
            $wpdb->delete($sources, array('enrollment_id' => (int) $enrollment_id), array('%d'));
            $wpdb->delete($enrollments, array('id' => (int) $enrollment_id), array('%d'));
        }
    }

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);

        if ($order instanceof WC_Order) {
            $order->delete(true);
        }
    }

    foreach ($product_ids as $product_id) {
        wp_delete_post($product_id, true);
    }

    if (is_int($course_id)) {
        wp_delete_post($course_id, true);
    }

    if (is_int($customer_id)) {
        wp_delete_user($customer_id);
    }
}

function assertEnrollmentState(int $user_id, int $course_id, int $source_count, string $status): void
{
    global $wpdb;

    $enrollments = $wpdb->prefix . 'kaanbal_enrollments';
    $sources = $wpdb->prefix . 'kaanbal_enrollment_sources';
    $enrollment = $wpdb->get_row($wpdb->prepare('SELECT id, status FROM %i WHERE user_id = %d AND course_id = %d', $enrollments, $user_id, $course_id), ARRAY_A);

    if (! is_array($enrollment) || $status !== $enrollment['status']) {
        throw new RuntimeException('The enrollment state does not match the expected state.');
    }

    $actual_source_count = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE enrollment_id = %d', $sources, $enrollment['id']));

    if ($source_count !== $actual_source_count) {
        throw new RuntimeException('The enrollment source count does not match the expected count.');
    }
}
