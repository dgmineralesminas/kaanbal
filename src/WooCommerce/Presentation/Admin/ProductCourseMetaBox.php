<?php

declare(strict_types=1);

namespace Kaanbal\WooCommerce\Presentation\Admin;

use Kaanbal\Courses\Infrastructure\ContentTypes;
use Kaanbal\Enrollment\Infrastructure\ProductCourseRepository;

final class ProductCourseMetaBox
{
    private const NONCE_NAME = 'kaanbal_product_courses_nonce';

    /**
     * Course statuses that can be newly associated with a product. They match the
     * statuses GrantCoursesFromOrder accepts (anything that is not in the trash).
     */
    private const ASSIGNABLE_STATUSES = array('publish', 'future', 'draft', 'pending', 'private');

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
        $courses  = $this->listableCourses($selected);

        if (array() === $courses) {
            echo '<p>' . esc_html__('No Kaanbal courses are available yet.', 'kaanbal') . '</p>';

            return;
        }

        echo '<p>' . esc_html__('Select the courses granted when this product is purchased.', 'kaanbal') . '</p>';

        foreach ($courses as $course) {
            $field_id = 'kaanbal-course-' . $course->ID;
            echo '<input name="kaanbal_listed_course_ids[]" type="hidden" value="' . esc_attr((string) $course->ID) . '" />';
            echo '<p><label for="' . esc_attr($field_id) . '">';
            echo '<input id="' . esc_attr($field_id) . '" name="kaanbal_course_ids[]" type="checkbox" value="' . esc_attr((string) $course->ID) . '" ' . checked(in_array($course->ID, $selected, true), true, false) . ' /> ';
            echo esc_html(get_the_title($course));

            $status_label = $this->statusLabel($course);

            if ('' !== $status_label) {
                echo ' <em>(' . esc_html($status_label) . ')</em>';
            }

            echo '</label></p>';
        }

        if (array() !== array_filter($courses, static fn (\WP_Post $course): bool => 'trash' === $course->post_status)) {
            echo '<p class="description">' . esc_html__('Trashed courses do not grant access until they are restored.', 'kaanbal') . '</p>';
        }
    }

    public function save(int $product_id): void
    {
        if (! $this->canSave($product_id)) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- The nonce and capability are verified above by canSave().
        $course_ids = isset($_POST['kaanbal_course_ids']) && is_array($_POST['kaanbal_course_ids'])
            ? array_map('absint', wp_unslash($_POST['kaanbal_course_ids']))
            : array();
        $listed_ids = isset($_POST['kaanbal_listed_course_ids']) && is_array($_POST['kaanbal_listed_course_ids'])
            ? array_map('absint', wp_unslash($_POST['kaanbal_listed_course_ids']))
            : array();
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $existing         = $this->product_courses->findCoursesByProduct($product_id);
        $valid_course_ids = array();

        foreach ($course_ids as $course_id) {
            $course = get_post($course_id);

            if (! $course instanceof \WP_Post || ContentTypes::COURSE !== $course->post_type) {
                continue;
            }

            // A trashed course can stay associated (so restoring it restores access) but cannot be newly attached.
            if ('trash' === $course->post_status && ! in_array($course_id, $existing, true)) {
                continue;
            }

            $valid_course_ids[] = $course_id;
        }

        $this->product_courses->syncCourses($product_id, $valid_course_ids, $listed_ids);
    }

    /**
     * Every assignable course plus any course already associated with the product,
     * so that saving the form never drops an association the editor could not see.
     *
     * @param list<int> $selected
     * @return list<\WP_Post>
     */
    private function listableCourses(array $selected): array
    {
        $courses = get_posts(
            array(
                'post_type'      => ContentTypes::COURSE,
                'post_status'    => self::ASSIGNABLE_STATUSES,
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        $listed_ids = array_map(static fn (\WP_Post $course): int => $course->ID, $courses);
        $missing    = array_values(array_diff($selected, $listed_ids));

        if (array() !== $missing) {
            $courses = array_merge(
                $courses,
                get_posts(
                    array(
                        'post_type'      => ContentTypes::COURSE,
                        'post_status'    => 'any',
                        'post__in'       => $missing,
                        'posts_per_page' => -1,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                    )
                ),
                get_posts(
                    array(
                        'post_type'      => ContentTypes::COURSE,
                        'post_status'    => 'trash',
                        'post__in'       => $missing,
                        'posts_per_page' => -1,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                    )
                )
            );
        }

        return array_values($courses);
    }

    private function statusLabel(\WP_Post $course): string
    {
        return match ($course->post_status) {
            'publish' => '',
            'future'  => __('Scheduled', 'kaanbal'),
            'draft'   => __('Draft', 'kaanbal'),
            'pending' => __('Pending review', 'kaanbal'),
            'private' => __('Private', 'kaanbal'),
            'trash'   => __('In trash', 'kaanbal'),
            default   => $course->post_status,
        };
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
