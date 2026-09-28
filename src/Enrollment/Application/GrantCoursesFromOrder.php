<?php

declare(strict_types=1);

namespace Kaanbal\Enrollment\Application;

use Kaanbal\Courses\Infrastructure\ContentTypes;
use Kaanbal\Enrollment\Infrastructure\ProductCourseRepository;
use Kaanbal\WooCommerce\Infrastructure\WooCommerceOrderAdapter;

final class GrantCoursesFromOrder
{
    public function __construct(
        private readonly ProductCourseRepository $product_courses,
        private readonly EnrollmentService $enrollments,
    ) {
    }

    public function grant(WooCommerceOrderAdapter $order): void
    {
        if (0 === $order->customerId()) {
            return;
        }

        $courses_by_product = $this->product_courses->findCoursesByProducts($order->productIds());

        foreach ($order->items() as $item) {
            foreach ($courses_by_product[$item['product_id']] ?? array() as $course_id) {
                if (! $this->isValidCourse($course_id)) {
                    continue;
                }

                $this->enrollments->grantWooCommerceCourse(
                    $order->customerId(),
                    $course_id,
                    $order->id(),
                    $item['product_id'],
                    $item['order_item_id']
                );
            }
        }
    }

    private function isValidCourse(int $course_id): bool
    {
        $course = get_post($course_id);

        return $course instanceof \WP_Post
            && ContentTypes::COURSE === $course->post_type
            && 'trash' !== $course->post_status;
    }
}
