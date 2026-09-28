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
$extra_course_ids = array();
$product_ids = array();
$order_ids   = array();
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The fixture preserves the request payload before testing authorized and unauthorized saves.
$previous_post = $_POST;
$previous_user_id = get_current_user_id();

try {
    global $wpdb;

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

    // AC-019 — a valid nonce is not enough: a user who cannot edit the product is rejected.
    wp_set_current_user($customer_id);
    $_POST = array(
        'kaanbal_product_courses_nonce' => wp_create_nonce('kaanbal_save_product_courses'),
        'kaanbal_course_ids'            => array((string) $course_id),
        'kaanbal_listed_course_ids'     => array((string) $course_id),
    );
    $meta_box->save($product_ids[1]);

    if (array() !== $product_courses->findCoursesByProduct($product_ids[1])) {
        throw new RuntimeException('A user without the edit_post capability changed product-course associations.');
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

    // Later fixtures insert products, which fires save_post_product; a leftover form payload
    // with a valid nonce would silently attach courses to every new product.
    $_POST = array();
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

    // CODE-004 — re-granting a revoked source reuses the row and records the new grant time.
    $revoked_source_filter = array(
        'order_id'   => $reactivation_order->get_id(),
        'product_id' => $product_ids[0],
    );
    $wpdb->update($wpdb->prefix . 'kaanbal_enrollment_sources', array('granted_at' => '2000-01-01 00:00:00'), $revoked_source_filter);
    $reactivation_order->update_status('processing');
    assertEnrollmentState($customer_id, $course_id, 4, 'active');
    $regranted_source = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT granted_at, revoked_at FROM %i WHERE order_id = %d AND product_id = %d',
            $wpdb->prefix . 'kaanbal_enrollment_sources',
            $reactivation_order->get_id(),
            $product_ids[0]
        ),
        ARRAY_A
    );

    if (! is_array($regranted_source) || null !== $regranted_source['revoked_at'] || '2000-01-01 00:00:00' === $regranted_source['granted_at']) {
        throw new RuntimeException('Re-granting a revoked source did not clear revoked_at and record the new grant time.');
    }

    // Re-processing a source that is already valid keeps its original grant time.
    $wpdb->update($wpdb->prefix . 'kaanbal_enrollment_sources', array('granted_at' => '2001-01-01 00:00:00'), $revoked_source_filter);
    $reactivation_order->update_status('completed');

    if ('2001-01-01 00:00:00' !== $wpdb->get_var($wpdb->prepare('SELECT granted_at FROM %i WHERE order_id = %d AND product_id = %d', $wpdb->prefix . 'kaanbal_enrollment_sources', $reactivation_order->get_id(), $product_ids[0]))) {
        throw new RuntimeException('Re-processing a valid source changed its grant time.');
    }

    $guest_order = wc_create_order();
    $guest_order->add_product($product_a, 1);
    $guest_order->calculate_totals();
    $guest_order->save();
    $order_ids[] = $guest_order->get_id();
    $guest_order->update_status('processing');

    assertEnrollmentState($customer_id, $course_id, 4, 'active');

    // CODE-005 — the guest order explains, once, why no access was granted.
    $guest_order->update_status('completed');
    assertKaanbalNoteCount($guest_order->get_id(), 1);

    // Following the note's instructions grants access.
    $guest_order = wc_get_order($guest_order->get_id());
    $guest_order->set_customer_id($customer_id);
    $guest_order->save();
    $guest_order->update_status('processing');
    assertEnrollmentState($customer_id, $course_id, 5, 'active');
    assertKaanbalNoteCount($guest_order->get_id(), 1);

    // CODE-003 — saving a product through its real form must not drop associations to
    // courses that are not published, and the form must offer every assignable course.
    $form_product_id   = createProductFixture('WooCommerce metabox product');
    $product_ids[]     = $form_product_id;
    $draft_course_id   = createCourseFixture('WooCommerce draft course');
    $future_course_id  = createCourseFixture('WooCommerce scheduled course');
    $trashed_form_course_id = createCourseFixture('WooCommerce trashed associated course');
    $unassociated_trash_id  = createCourseFixture('WooCommerce trashed unassociated course');
    $extra_course_ids  = array_merge($extra_course_ids, array($draft_course_id, $future_course_id, $trashed_form_course_id, $unassociated_trash_id));

    $product_courses->attachCourse($form_product_id, $draft_course_id);
    $product_courses->attachCourse($form_product_id, $trashed_form_course_id);
    wp_update_post(array('ID' => $draft_course_id, 'post_status' => 'draft'));
    wp_update_post(array('ID' => $future_course_id, 'post_status' => 'future', 'post_date' => gmdate('Y-m-d H:i:s', time() + WEEK_IN_SECONDS), 'post_date_gmt' => gmdate('Y-m-d H:i:s', time() + WEEK_IN_SECONDS)));
    wp_trash_post($trashed_form_course_id);
    wp_trash_post($unassociated_trash_id);

    // Editing anything else on the product and saving keeps the existing associations.
    submitProductCourseForm($meta_box, $form_product_id);
    assertProductCourses($product_courses, $form_product_id, array($draft_course_id, $trashed_form_course_id), 'Saving the product form dropped associations to unpublished courses.');

    // The editor sees the associated trashed course, checked and labelled, instead of a hidden association.
    ob_start();
    $meta_box->render(get_post($form_product_id));
    $form_html = (string) ob_get_clean();

    if (1 !== preg_match('/value="' . $trashed_form_course_id . '"\s+checked=/', $form_html) || ! str_contains($form_html, 'In trash')) {
        throw new RuntimeException('The product form hides an associated trashed course.');
    }

    // A scheduled course is offered by the form and can be associated.
    submitProductCourseForm($meta_box, $form_product_id, array($future_course_id));
    assertProductCourses($product_courses, $form_product_id, array($draft_course_id, $future_course_id, $trashed_form_course_id), 'A scheduled course could not be associated from the product form.');

    // Unchecking a listed course removes only that association.
    submitProductCourseForm($meta_box, $form_product_id, array(), array($draft_course_id));
    assertProductCourses($product_courses, $form_product_id, array($future_course_id, $trashed_form_course_id), 'Unchecking a course did not remove exactly that association.');

    // A trashed course that was not associated cannot be newly attached.
    $_POST = array(
        'kaanbal_product_courses_nonce' => wp_create_nonce('kaanbal_save_product_courses'),
        'kaanbal_course_ids'            => array((string) $unassociated_trash_id, (string) $future_course_id),
        'kaanbal_listed_course_ids'     => array((string) $future_course_id, (string) $trashed_form_course_id),
    );
    $meta_box->save($form_product_id);
    assertProductCourses($product_courses, $form_product_id, array($future_course_id), 'The form attached a trashed course or kept an unchecked listed course.');

    // Courses that the submitted form did not list are never detached.
    $product_courses->attachCourse($form_product_id, $draft_course_id);
    $_POST = array(
        'kaanbal_product_courses_nonce' => wp_create_nonce('kaanbal_save_product_courses'),
        'kaanbal_course_ids'            => array(),
        'kaanbal_listed_course_ids'     => array((string) $future_course_id),
    );
    $meta_box->save($form_product_id);
    assertProductCourses($product_courses, $form_product_id, array($draft_course_id), 'A course that the form did not list was detached.');

    // AC-008 / SC-007 — one product grants several courses (bundle).
    $bundle_course_ids = array(
        createCourseFixture('WooCommerce bundle course 1'),
        createCourseFixture('WooCommerce bundle course 2'),
        createCourseFixture('WooCommerce bundle course 3'),
    );
    $extra_course_ids  = array_merge($extra_course_ids, $bundle_course_ids);
    $bundle_product_id = createProductFixture('WooCommerce bundle product');
    $product_ids[]     = $bundle_product_id;

    foreach ($bundle_course_ids as $bundle_course_id) {
        $product_courses->attachCourse($bundle_product_id, $bundle_course_id);
    }

    $bundle_order   = createOrderFixture($customer_id);
    $order_ids[]    = $bundle_order->get_id();
    $bundle_item_id = (int) $bundle_order->add_product(loadProductFixture($bundle_product_id), 1);
    $bundle_order->calculate_totals();
    $bundle_order->save();
    $bundle_order->update_status('processing');

    foreach ($bundle_course_ids as $bundle_course_id) {
        assertEnrollmentState($customer_id, $bundle_course_id, 1, 'active');
        // AC-007 / SC-006 — the source keeps order, order item and product references.
        assertSourceTraceability($customer_id, $bundle_course_id, $bundle_order->get_id(), $bundle_product_id, $bundle_item_id);
    }

    // AC-009 / SC-008 — two products of the same order grant the same course.
    $overlap_course_id  = createCourseFixture('WooCommerce overlapping course');
    $extra_course_ids[] = $overlap_course_id;
    $overlap_product_ids = array(
        createProductFixture('WooCommerce overlapping product A'),
        createProductFixture('WooCommerce overlapping product B'),
    );
    $product_ids = array_merge($product_ids, $overlap_product_ids);

    foreach ($overlap_product_ids as $overlap_product_id) {
        $product_courses->attachCourse($overlap_product_id, $overlap_course_id);
    }

    $overlap_order = createOrderFixture($customer_id);
    $order_ids[]   = $overlap_order->get_id();
    $overlap_items = array();

    foreach ($overlap_product_ids as $overlap_product_id) {
        $overlap_items[$overlap_product_id] = (int) $overlap_order->add_product(loadProductFixture($overlap_product_id), 1);
    }

    $overlap_order->calculate_totals();
    $overlap_order->save();
    $overlap_order->update_status('processing');
    assertEnrollmentState($customer_id, $overlap_course_id, 2, 'active');

    foreach ($overlap_items as $overlap_product_id => $overlap_item_id) {
        assertSourceTraceability($customer_id, $overlap_course_id, $overlap_order->get_id(), $overlap_product_id, $overlap_item_id);
    }

    $overlap_order->update_status('completed');
    assertEnrollmentState($customer_id, $overlap_course_id, 2, 'active');

    // AC-004 / SC-004 — a product without courses is academically ignored and WooCommerce continues.
    $enrollments_before    = countEnrollmentsForUser($customer_id);
    $no_course_product_id  = createProductFixture('WooCommerce product without courses');
    $product_ids[]         = $no_course_product_id;
    $no_course_order       = createOrderFixture($customer_id);
    $order_ids[]           = $no_course_order->get_id();
    $no_course_order->add_product(loadProductFixture($no_course_product_id), 1);
    $no_course_order->calculate_totals();
    $no_course_order->save();
    $no_course_order->update_status('processing');
    $no_course_order->update_status('completed');

    if ($enrollments_before !== countEnrollmentsForUser($customer_id)) {
        throw new RuntimeException('A product without courses created an enrollment.');
    }

    if ('completed' !== wc_get_order($no_course_order->get_id())->get_status()) {
        throw new RuntimeException('WooCommerce did not complete an order containing a product without courses.');
    }

    // EC-003 — associations to trashed or deleted courses do not produce enrollments.
    $trashed_course_id  = createCourseFixture('WooCommerce trashed course');
    $deleted_course_id  = createCourseFixture('WooCommerce deleted course');
    $extra_course_ids[] = $trashed_course_id;
    $orphan_product_id  = createProductFixture('WooCommerce product with removed courses');
    $product_ids[]      = $orphan_product_id;
    $product_courses->attachCourse($orphan_product_id, $trashed_course_id);
    $product_courses->attachCourse($orphan_product_id, $deleted_course_id);
    wp_trash_post($trashed_course_id);
    wp_delete_post($deleted_course_id, true);

    $orphan_order = createOrderFixture($customer_id);
    $order_ids[]  = $orphan_order->get_id();
    $orphan_order->add_product(loadProductFixture($orphan_product_id), 1);
    $orphan_order->calculate_totals();
    $orphan_order->save();
    $orphan_order->update_status('processing');

    assertNoEnrollment($customer_id, $trashed_course_id);
    assertNoEnrollment($customer_id, $deleted_course_id);

    if ($enrollments_before !== countEnrollmentsForUser($customer_id)) {
        throw new RuntimeException('An association to a removed course created an enrollment.');
    }

    echo "WooCommerce enrollment integration: PASS\n";
} finally {
    global $wpdb;

    $_POST = $previous_post;
    wp_set_current_user($previous_user_id);

    if ($wpdb instanceof wpdb && is_int($customer_id)) {
        $sources        = $wpdb->prefix . 'kaanbal_enrollment_sources';
        $enrollments    = $wpdb->prefix . 'kaanbal_enrollments';
        $enrollment_ids = $wpdb->get_col($wpdb->prepare('SELECT id FROM %i WHERE user_id = %d', $enrollments, $customer_id));

        foreach ($enrollment_ids as $enrollment_id) {
            $wpdb->delete($sources, array('enrollment_id' => (int) $enrollment_id), array('%d'));
            $wpdb->delete($enrollments, array('id' => (int) $enrollment_id), array('%d'));
        }
    }

    if ($wpdb instanceof wpdb) {
        foreach ($product_ids as $product_id) {
            $wpdb->delete($wpdb->prefix . 'kaanbal_product_courses', array('product_id' => (int) $product_id), array('%d'));
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

    foreach ($extra_course_ids as $extra_course_id) {
        wp_delete_post($extra_course_id, true);
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

function assertNoEnrollment(int $user_id, int $course_id): void
{
    global $wpdb;

    $enrollments = $wpdb->prefix . 'kaanbal_enrollments';

    if (null !== $wpdb->get_var($wpdb->prepare('SELECT id FROM %i WHERE user_id = %d AND course_id = %d', $enrollments, $user_id, $course_id))) {
        throw new RuntimeException('An unexpected enrollment was created.');
    }
}

function countEnrollmentsForUser(int $user_id): int
{
    global $wpdb;

    return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d', $wpdb->prefix . 'kaanbal_enrollments', $user_id));
}

function assertSourceTraceability(int $user_id, int $course_id, int $order_id, int $product_id, int $order_item_id): void
{
    global $wpdb;

    $enrollments = $wpdb->prefix . 'kaanbal_enrollments';
    $sources     = $wpdb->prefix . 'kaanbal_enrollment_sources';
    $source_id   = $wpdb->get_var(
        $wpdb->prepare(
            'SELECT s.id FROM %i s INNER JOIN %i e ON e.id = s.enrollment_id WHERE e.user_id = %d AND e.course_id = %d AND s.source_type = %s AND s.order_id = %d AND s.product_id = %d AND s.order_item_id = %d AND s.revoked_at IS NULL',
            $sources,
            $enrollments,
            $user_id,
            $course_id,
            'woocommerce',
            $order_id,
            $product_id,
            $order_item_id
        )
    );

    if (null === $source_id) {
        throw new RuntimeException('The WooCommerce source does not keep the order, product and order item references.');
    }
}

function createCourseFixture(string $title): int
{
    $course_id = wp_insert_post(array('post_type' => 'kaanbal_course', 'post_title' => $title, 'post_status' => 'publish'));

    if (0 === $course_id || is_wp_error($course_id)) {
        throw new RuntimeException('A course fixture could not be created.');
    }

    return (int) $course_id;
}

function createProductFixture(string $title): int
{
    $product_id = wp_insert_post(array('post_type' => 'product', 'post_title' => $title, 'post_status' => 'publish'));

    if (0 === $product_id || is_wp_error($product_id)) {
        throw new RuntimeException('A product fixture could not be created.');
    }

    wp_set_object_terms($product_id, 'simple', 'product_type');
    update_post_meta($product_id, '_regular_price', '100');
    update_post_meta($product_id, '_price', '100');

    return (int) $product_id;
}

function loadProductFixture(int $product_id): WC_Product
{
    $product = wc_get_product($product_id);

    if (! $product instanceof WC_Product) {
        throw new RuntimeException('A product fixture could not be loaded.');
    }

    return $product;
}

function createOrderFixture(int $customer_id): WC_Order
{
    $order = wc_create_order(array('customer_id' => $customer_id));

    if (! $order instanceof WC_Order) {
        throw new RuntimeException('An order fixture could not be created.');
    }

    return $order;
}

/**
 * Renders the product metabox, reads its inputs as a browser would and saves them.
 *
 * @param list<int> $check   Course IDs to check in addition to the rendered state.
 * @param list<int> $uncheck Course IDs to uncheck.
 */
function submitProductCourseForm(ProductCourseMetaBox $meta_box, int $product_id, array $check = array(), array $uncheck = array()): void
{
    $product = get_post($product_id);

    if (! $product instanceof WP_Post) {
        throw new RuntimeException('The product fixture could not be loaded for the metabox form.');
    }

    ob_start();
    $meta_box->render($product);
    $html = (string) ob_get_clean();

    preg_match_all('/<input\b[^>]*>/i', $html, $inputs);
    $post = array('kaanbal_course_ids' => array(), 'kaanbal_listed_course_ids' => array());

    foreach ($inputs[0] as $input) {
        preg_match('/\bname="([^"]*)"/', $input, $name);
        preg_match('/\bvalue="([^"]*)"/', $input, $value);
        preg_match('/\btype="([^"]*)"/', $input, $type);
        $name  = $name[1] ?? '';
        $value = html_entity_decode($value[1] ?? '');

        if ('checkbox' === ($type[1] ?? '')) {
            $is_checked = 1 === preg_match('/\bchecked=/', $input);
            $is_checked = ($is_checked || in_array((int) $value, $check, true)) && ! in_array((int) $value, $uncheck, true);

            if ($is_checked) {
                $post['kaanbal_course_ids'][] = $value;
            }

            continue;
        }

        if ('kaanbal_listed_course_ids[]' === $name) {
            $post['kaanbal_listed_course_ids'][] = $value;
        } elseif ('' !== $name && ! str_ends_with($name, '[]')) {
            $post[$name] = $value;
        }
    }

    foreach ($check as $course_id) {
        if (! in_array((string) $course_id, $post['kaanbal_listed_course_ids'], true)) {
            throw new RuntimeException('The product form does not offer an assignable course.');
        }
    }

    $_POST = $post;
    $meta_box->save($product_id);
}

/** @param list<int> $expected */
function assertProductCourses(ProductCourseRepository $product_courses, int $product_id, array $expected, string $message): void
{
    sort($expected);

    if ($expected !== $product_courses->findCoursesByProduct($product_id)) {
        throw new RuntimeException(esc_html($message));
    }
}

function assertKaanbalNoteCount(int $order_id, int $expected): void
{
    $notes = array_filter(
        wc_get_order_notes(array('order_id' => $order_id)),
        static fn (object $note): bool => str_starts_with((string) $note->content, 'Kaanbal:')
    );

    if ($expected !== count($notes)) {
        throw new RuntimeException(esc_html(sprintf('Expected %d Kaanbal order notes, found %d.', $expected, count($notes))));
    }
}
