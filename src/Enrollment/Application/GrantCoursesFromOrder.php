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

    public const GRANTED = 'granted';

    public const NOTHING_TO_GRANT = 'nothing_to_grant';

    public const GUEST_ORDER_WITH_COURSES = 'guest_order_with_courses';

    /** @return self::GRANTED|self::NOTHING_TO_GRANT|self::GUEST_ORDER_WITH_COURSES */
    public function grant(WooCommerceOrderAdapter $order): string
    {
        $courses_by_product = $this->product_courses->findCoursesByProducts($order->productIds());
        $grants             = array();

        foreach ($order->items() as $item) {
            foreach ($courses_by_product[$item['product_id']] ?? array() as $course_id) {
                if ($this->isValidCourse($course_id)) {
                    $grants[] = array($course_id, $item['product_id'], $item['order_item_id']);
                }
            }
        }

        if (array() === $grants) {
            return self::NOTHING_TO_GRANT;
        }

        if (0 === $order->customerId()) {
            return self::GUEST_ORDER_WITH_COURSES;
        }

        foreach ($grants as [$course_id, $product_id, $order_item_id]) {
            $this->enrollments->grantWooCommerceCourse(
                $order->customerId(),
                $course_id,
                $order->id(),
                $product_id,
                $order_item_id
            );
        }

        return self::GRANTED;
    }

    private function isValidCourse(int $course_id): bool
    {
        $course = get_post($course_id);

        return $course instanceof \WP_Post
            && ContentTypes::COURSE === $course->post_type
            && 'trash' !== $course->post_status;
    }
}
