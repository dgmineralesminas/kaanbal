<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Presentation\Admin;

use Kaanbal\Courses\Infrastructure\ContentTypes;
use Kaanbal\Courses\Infrastructure\CurriculumRepository;
use Kaanbal\Courses\Video\VideoProviders;

final class CurriculumMetaBoxes
{
    private const COURSE_NONCE = 'kaanbal_course_meta_nonce';
    private const MODULE_NONCE = 'kaanbal_module_meta_nonce';
    private const LESSON_NONCE = 'kaanbal_lesson_meta_nonce';

    private const DURATION_META   = '_kaanbal_duration';
    private const INSTRUCTOR_META = '_kaanbal_instructor_name';
    private const PROVIDER_META   = '_kaanbal_video_provider';
    private const SOURCE_META     = '_kaanbal_video_source';

    public function register(): void
    {
        add_meta_box('kaanbal-course-details', __('Course details', 'kaanbal'), array($this, 'renderCourse'), ContentTypes::COURSE, 'normal', 'default');
        add_meta_box('kaanbal-module-course', __('Curriculum', 'kaanbal'), array($this, 'renderModule'), ContentTypes::MODULE, 'side', 'default');
        add_meta_box('kaanbal-lesson-curriculum', __('Curriculum and video', 'kaanbal'), array($this, 'renderLesson'), ContentTypes::LESSON, 'normal', 'default');
    }

    public function renderCourse(\WP_Post $post): void
    {
        wp_nonce_field('kaanbal_save_course_meta', self::COURSE_NONCE);

        $duration   = (string) get_post_meta($post->ID, self::DURATION_META, true);
        $instructor = (string) get_post_meta($post->ID, self::INSTRUCTOR_META, true);

        echo '<p><label for="kaanbal-duration">' . esc_html__('Duration', 'kaanbal') . '</label><br />';
        echo '<input class="widefat" id="kaanbal-duration" name="kaanbal_duration" type="text" value="' . esc_attr($duration) . '" /></p>';
        echo '<p><label for="kaanbal-instructor">' . esc_html__('Instructor', 'kaanbal') . '</label><br />';
        echo '<input class="widefat" id="kaanbal-instructor" name="kaanbal_instructor_name" type="text" value="' . esc_attr($instructor) . '" /></p>';
    }

    public function renderModule(\WP_Post $post): void
    {
        wp_nonce_field('kaanbal_save_module_meta', self::MODULE_NONCE);
        $this->renderCourseSelector((int) get_post_meta($post->ID, CurriculumRepository::COURSE_ID_META, true));
        echo '<p>' . esc_html__('Use the WordPress Order field to position this module within its course.', 'kaanbal') . '</p>';
    }

    public function renderLesson(\WP_Post $post): void
    {
        wp_nonce_field('kaanbal_save_lesson_meta', self::LESSON_NONCE);

        $module_id = (int) get_post_meta($post->ID, CurriculumRepository::MODULE_ID_META, true);
        $provider  = (string) get_post_meta($post->ID, self::PROVIDER_META, true);
        $source    = (string) get_post_meta($post->ID, self::SOURCE_META, true);

        $this->renderModuleSelector($module_id);

        echo '<p><label for="kaanbal-video-provider">' . esc_html__('Video provider', 'kaanbal') . '</label><br />';
        echo '<select class="widefat" id="kaanbal-video-provider" name="kaanbal_video_provider">';
        echo '<option value="">' . esc_html__('No video yet', 'kaanbal') . '</option>';
        echo '<option value="youtube"' . selected('youtube', $provider, false) . '>YouTube</option>';
        echo '</select></p>';
        echo '<p><label for="kaanbal-video-source">' . esc_html__('YouTube URL or video ID', 'kaanbal') . '</label><br />';
        echo '<input class="widefat" id="kaanbal-video-source" name="kaanbal_video_source" type="text" value="' . esc_attr($source) . '" /></p>';
        echo '<p>' . esc_html__('Use the WordPress Order field to position this lesson within its module.', 'kaanbal') . '</p>';
    }

    public function saveCourse(int $post_id): void
    {
        if (! $this->canSave($post_id, self::COURSE_NONCE, 'kaanbal_save_course_meta')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce and capability are verified above by canSave().
        $this->saveTextMeta($post_id, self::DURATION_META, 'kaanbal_duration');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce and capability are verified above by canSave().
        $this->saveTextMeta($post_id, self::INSTRUCTOR_META, 'kaanbal_instructor_name');
    }

    public function saveModule(int $post_id): void
    {
        if (! $this->canSave($post_id, self::MODULE_NONCE, 'kaanbal_save_module_meta')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce and capability are verified above by canSave().
        $this->saveRelationship($post_id, CurriculumRepository::COURSE_ID_META, 'kaanbal_course_id', ContentTypes::COURSE);
    }

    public function saveLesson(int $post_id): void
    {
        if (! $this->canSave($post_id, self::LESSON_NONCE, 'kaanbal_save_lesson_meta')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce and capability are verified above by canSave().
        $this->saveRelationship($post_id, CurriculumRepository::MODULE_ID_META, 'kaanbal_module_id', ContentTypes::MODULE);
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce and capability are verified above by canSave().
        $this->saveVideo($post_id);
    }

    private function renderCourseSelector(int $selected_id): void
    {
        $courses = get_posts(array('post_type' => ContentTypes::COURSE, 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));

        echo '<p><label for="kaanbal-course-id">' . esc_html__('Course', 'kaanbal') . '</label><br />';
        echo '<select class="widefat" id="kaanbal-course-id" name="kaanbal_course_id"><option value="">' . esc_html__('Select a course', 'kaanbal') . '</option>';

        foreach ($courses as $course) {
            echo '<option value="' . esc_attr((string) $course->ID) . '"' . selected($selected_id, $course->ID, false) . '>' . esc_html(get_the_title($course)) . '</option>';
        }

        echo '</select></p>';
    }

    private function renderModuleSelector(int $selected_id): void
    {
        $modules = get_posts(array('post_type' => ContentTypes::MODULE, 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));

        echo '<p><label for="kaanbal-module-id">' . esc_html__('Module', 'kaanbal') . '</label><br />';
        echo '<select class="widefat" id="kaanbal-module-id" name="kaanbal_module_id"><option value="">' . esc_html__('Select a module', 'kaanbal') . '</option>';

        foreach ($modules as $module) {
            echo '<option value="' . esc_attr((string) $module->ID) . '"' . selected($selected_id, $module->ID, false) . '>' . esc_html(get_the_title($module)) . '</option>';
        }

        echo '</select></p>';
    }

    private function canSave(int $post_id, string $nonce_name, string $action): bool
    {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
            return false;
        }

        if (! isset($_POST[$nonce_name])) {
            return false;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[$nonce_name]));

        return wp_verify_nonce($nonce, $action) && current_user_can('edit_post', $post_id);
    }

    private function saveTextMeta(int $post_id, string $meta_key, string $field): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The calling save method verified the nonce and capability.
        if (! isset($_POST[$field])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The calling save method verified the nonce and capability.
        update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$field])));
    }

    private function saveRelationship(int $post_id, string $meta_key, string $field, string $expected_type): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The calling save method verified the nonce and capability.
        if (! isset($_POST[$field])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The calling save method verified the nonce and capability.
        $related_id = absint(wp_unslash($_POST[$field]));

        if (0 === $related_id) {
            delete_post_meta($post_id, $meta_key);

            return;
        }

        $related = get_post($related_id);

        if ($related instanceof \WP_Post && $expected_type === $related->post_type) {
            update_post_meta($post_id, $meta_key, $related_id);
        }
    }

    private function saveVideo(int $post_id): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The calling save method verified the nonce and capability.
        if (! isset($_POST['kaanbal_video_provider'], $_POST['kaanbal_video_source'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The calling save method verified the nonce and capability.
        $provider_key = sanitize_key(wp_unslash($_POST['kaanbal_video_provider']));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The calling save method verified the nonce and capability.
        $source       = sanitize_text_field(wp_unslash($_POST['kaanbal_video_source']));

        if ('' === $provider_key || '' === $source) {
            delete_post_meta($post_id, self::PROVIDER_META);
            delete_post_meta($post_id, self::SOURCE_META);

            return;
        }

        $provider = (new VideoProviders())->forKey($provider_key);
        $video_id = $provider instanceof \Kaanbal\Courses\Video\VideoProvider ? $provider->normalize($source) : null;

        if (null !== $video_id) {
            update_post_meta($post_id, self::PROVIDER_META, $provider->key());
            update_post_meta($post_id, self::SOURCE_META, $video_id);
        }
    }
}
