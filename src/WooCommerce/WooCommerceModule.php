<?php

declare(strict_types=1);

namespace Kaanbal\WooCommerce;

use Kaanbal\Bootstrap\BootableService;
use Kaanbal\Enrollment\Application\EnrollmentService;
use Kaanbal\Enrollment\Application\GrantCoursesFromOrder;
use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Enrollment\Infrastructure\EnrollmentSourceRepository;
use Kaanbal\Enrollment\Infrastructure\ProductCourseRepository;
use Kaanbal\WooCommerce\Infrastructure\WooCommerceOrderAdapter;
use Kaanbal\WooCommerce\Presentation\Admin\ProductCourseMetaBox;

final class WooCommerceModule implements BootableService
{
    public function register(): void
    {
        add_action('plugins_loaded', array($this, 'registerWhenWooCommerceIsAvailable'), 20);
    }

    public function registerWhenWooCommerceIsAvailable(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        $product_courses = new ProductCourseRepository();
        $enrollments     = new EnrollmentService(new EnrollmentRepository(), new EnrollmentSourceRepository());
        $grant_courses   = new GrantCoursesFromOrder($product_courses, $enrollments);
        $meta_box        = new ProductCourseMetaBox($product_courses);

        add_action('add_meta_boxes_product', array($meta_box, 'register'));
        add_action('save_post_product', array($meta_box, 'save'));
        add_action('woocommerce_order_status_processing', array($this, 'grantCourses'), 20, 2);
        add_action('woocommerce_order_status_completed', array($this, 'grantCourses'), 20, 2);
        add_action('woocommerce_order_status_cancelled', array($this, 'revokeCourses'), 20);
        add_action('woocommerce_order_status_refunded', array($this, 'revokeCourses'), 20);

        $this->grant_courses = $grant_courses;
        $this->enrollments   = $enrollments;
    }

    private ?GrantCoursesFromOrder $grant_courses = null;

    private ?EnrollmentService $enrollments = null;

    public function grantCourses(int $order_id, ?object $order = null): void
    {
        if (! $this->grant_courses instanceof GrantCoursesFromOrder) {
            return;
        }

        if (! is_object($order)) {
            return;
        }

        $this->grant_courses->grant(new WooCommerceOrderAdapter($order));
    }

    public function revokeCourses(int $order_id): void
    {
        $this->enrollments?->revokeWooCommerceOrder($order_id);
    }
}
