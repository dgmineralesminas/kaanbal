<?php

declare(strict_types=1);

namespace Kaanbal\WooCommerce\Presentation\Admin;

use Kaanbal\Courses\Infrastructure\ContentTypes;
use Kaanbal\Enrollment\Infrastructure\ProductCourseRepository;

final class ProductCourseMetaBox
{
    private const NONCE_NAME = 'kaanbal_product_courses_nonce';

    public function __construct(private readonly ProductCourseRepository $product_courses)
    {
    }

    public function register(): void
    {
        add_meta_box(
            'kaanbal-product-courses',
            __('Kaanbal courses', 'kaanbal'),
            array($this, 'render'),
            'product',
            'side',
            'default'
        );
    }

    public function render(\WP_Post $product): void
    {
        wp_nonce_field('kaanbal_save_product_courses', self::NONCE_NAME);

        $selected = $this->product_courses->findCoursesByProduct($product->ID);
        $courses  = get_posts(
            array(
                'post_type'      => ContentTypes::COURSE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        if (array() === $courses) {
            echo '<p>' . esc_html__('No published Kaanbal courses are available.', 'kaanbal') . '</p>';

            return;
        }

        echo '<p>' . esc_html__('Select the courses granted when this product is purchased.', 'kaanbal') . '</p>';

        foreach ($courses as $course) {
            $field_id = 'kaanbal-course-' . $course->ID;
            echo '<p><label for="' . esc_attr($field_id) . '">';
            echo '<input id="' . esc_attr($field_id) . '" name="kaanbal_course_ids[]" type="checkbox" value="' . esc_attr((string) $course->ID) . '" ' . checked(in_array($course->ID, $selected, true), true, false) . ' /> ';
            echo esc_html(get_the_title($course));
            echo '</label></p>';
        }
    }

    public function save(int $product_id): void
    {
        if (! $this->canSave($product_id)) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce and capability are verified above by canSave().
        $course_ids = isset($_POST['kaanbal_course_ids']) && is_array($_POST['kaanbal_course_ids'])
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce and capability are verified above by canSave().
            ? array_map('absint', wp_unslash($_POST['kaanbal_course_ids']))
            : array();

        $valid_course_ids = array();

        foreach ($course_ids as $course_id) {
            $course = get_post($course_id);

            if ($course instanceof \WP_Post && ContentTypes::COURSE === $course->post_type && 'trash' !== $course->post_status) {
                $valid_course_ids[] = $course_id;
            }
        }

        $this->product_courses->syncCourses($product_id, $valid_course_ids);
    }

    private function canSave(int $product_id): bool
    {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($product_id)) {
            return false;
        }

        $product = get_post($product_id);

        if (! $product instanceof \WP_Post || 'product' !== $product->post_type || ! isset($_POST[self::NONCE_NAME])) {
            return false;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]));

        return wp_verify_nonce($nonce, 'kaanbal_save_product_courses') && current_user_can('edit_post', $product_id);
    }
}
