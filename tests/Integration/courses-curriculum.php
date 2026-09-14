<?php

declare(strict_types=1);

$wordpress_path = getenv('KAANBAL_WP_PATH');

if (! is_string($wordpress_path) || '' === $wordpress_path) {
    fwrite(STDERR, "KAANBAL_WP_PATH must point to the WordPress root.\n");
    exit(1);
}

$wordpress_bootstrap = rtrim($wordpress_path, DIRECTORY_SEPARATOR) . '/wp-load.php';

if (! is_file($wordpress_bootstrap)) {
    fwrite(STDERR, "wp-load.php was not found at KAANBAL_WP_PATH.\n");
    exit(1);
}

require_once $wordpress_bootstrap;

$plugin_file      = dirname(__DIR__, 2) . '/kaanbal.php';
$created_ids      = array();
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The fixture preserves the request payload before creating isolated test data.
$previous_post    = $_POST;
$previous_user_id = get_current_user_id();

try {
    require_once $plugin_file;
    do_action('init');

    foreach (array('kaanbal_course', 'kaanbal_module', 'kaanbal_lesson') as $post_type) {
        if (! post_type_exists($post_type)) {
            throw new RuntimeException(sprintf('The %s content type is not registered.', $post_type));
        }
    }

    $course_id = wp_insert_post(array('post_type' => 'kaanbal_course', 'post_title' => 'Kaanbal integration course', 'post_status' => 'draft'));
    $module_b_id = wp_insert_post(array('post_type' => 'kaanbal_module', 'post_title' => 'Second module', 'post_status' => 'draft', 'menu_order' => 2));
    $module_a_id = wp_insert_post(array('post_type' => 'kaanbal_module', 'post_title' => 'First module', 'post_status' => 'draft', 'menu_order' => 1));
    $lesson_b_id = wp_insert_post(array('post_type' => 'kaanbal_lesson', 'post_title' => 'Second lesson', 'post_status' => 'draft', 'menu_order' => 2));
    $lesson_a_id = wp_insert_post(array('post_type' => 'kaanbal_lesson', 'post_title' => 'First lesson', 'post_status' => 'draft', 'menu_order' => 1));
    $created_ids = array((int) $course_id, (int) $module_b_id, (int) $module_a_id, (int) $lesson_b_id, (int) $lesson_a_id);

    if (in_array(0, $created_ids, true)) {
        throw new RuntimeException('The curriculum fixture could not be created.');
    }

    update_post_meta($module_a_id, '_kaanbal_course_id', $course_id);
    update_post_meta($module_b_id, '_kaanbal_course_id', $course_id);
    update_post_meta($lesson_a_id, '_kaanbal_module_id', $module_a_id);
    update_post_meta($lesson_b_id, '_kaanbal_module_id', $module_a_id);

    $service    = new Kaanbal\Courses\Application\CurriculumService(new Kaanbal\Courses\Infrastructure\CurriculumRepository());
    $curriculum = $service->forCourse((int) $course_id);
    $hierarchy  = $service->hierarchyForLesson((int) $lesson_a_id);

    if (! is_array($curriculum) || $module_a_id !== $curriculum['modules'][0]['module']->ID || $lesson_a_id !== $curriculum['modules'][0]['lessons'][0]->ID) {
        throw new RuntimeException('Curriculum ordering is not deterministic.');
    }

    if (! is_array($hierarchy) || $module_a_id !== $hierarchy['module']->ID || $course_id !== $hierarchy['course']->ID) {
        throw new RuntimeException('Lesson hierarchy could not be resolved.');
    }

    if (! isset($curriculum['modules'][1]) || $module_b_id !== $curriculum['modules'][1]['module']->ID || array() !== $curriculum['modules'][1]['lessons']) {
        throw new RuntimeException('An empty module is not represented correctly in the curriculum.');
    }

    delete_post_meta($module_a_id, '_kaanbal_course_id');
    delete_post_meta($module_b_id, '_kaanbal_course_id');

    if (array() !== $service->forCourse((int) $course_id)['modules']) {
        throw new RuntimeException('An empty course is not represented correctly in the curriculum.');
    }

    update_post_meta($module_a_id, '_kaanbal_course_id', $course_id);
    update_post_meta($module_b_id, '_kaanbal_course_id', $course_id);
    wp_update_post(array('ID' => $module_a_id, 'menu_order' => 7));
    wp_update_post(array('ID' => $module_b_id, 'menu_order' => 7));
    wp_update_post(array('ID' => $lesson_a_id, 'menu_order' => 7));
    wp_update_post(array('ID' => $lesson_b_id, 'menu_order' => 7));
    $tied_curriculum = $service->forCourse((int) $course_id);

    if (! is_array($tied_curriculum) || $module_b_id !== $tied_curriculum['modules'][0]['module']->ID || $lesson_b_id !== $tied_curriculum['modules'][1]['lessons'][0]->ID) {
        throw new RuntimeException('Curriculum tie-breaking is not deterministic.');
    }

    $administrator_ids = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ids'));

    if (! isset($administrator_ids[0])) {
        throw new RuntimeException('An administrator is required for the curriculum admin integration test.');
    }

    wp_set_current_user(0);
    $_POST = array(
        'kaanbal_course_meta_nonce' => 'invalid',
        'kaanbal_duration'          => 'Unauthorized change',
    );
    (new Kaanbal\Courses\Presentation\Admin\CurriculumMetaBoxes())->saveCourse((int) $course_id);

    if ('' !== (string) get_post_meta($course_id, '_kaanbal_duration', true)) {
        throw new RuntimeException('Course metadata was saved without a valid nonce and capability.');
    }

    wp_set_current_user((int) $administrator_ids[0]);
    $meta_boxes = new Kaanbal\Courses\Presentation\Admin\CurriculumMetaBoxes();

    $_POST = array(
        'kaanbal_course_meta_nonce' => wp_create_nonce('kaanbal_save_course_meta'),
        'kaanbal_duration'          => '4 hours',
        'kaanbal_instructor_name'   => 'Kaanbal Instructor',
    );
    $meta_boxes->saveCourse((int) $course_id);

    $_POST = array(
        'kaanbal_module_meta_nonce' => wp_create_nonce('kaanbal_save_module_meta'),
        'kaanbal_course_id'         => (string) $course_id,
    );
    $meta_boxes->saveModule((int) $module_a_id);

    $_POST = array(
        'kaanbal_lesson_meta_nonce' => wp_create_nonce('kaanbal_save_lesson_meta'),
        'kaanbal_module_id'         => (string) $module_a_id,
        'kaanbal_video_provider'    => 'youtube',
        'kaanbal_video_source'      => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    );
    $meta_boxes->saveLesson((int) $lesson_a_id);

    if ('4 hours' !== get_post_meta($course_id, '_kaanbal_duration', true) || 'Kaanbal Instructor' !== get_post_meta($course_id, '_kaanbal_instructor_name', true)) {
        throw new RuntimeException('Course metadata was not saved for an authorized editor.');
    }

    if ((string) $course_id !== get_post_meta($module_a_id, '_kaanbal_course_id', true) || (string) $module_a_id !== get_post_meta($lesson_a_id, '_kaanbal_module_id', true)) {
        throw new RuntimeException('Curriculum relationships were not saved for an authorized editor.');
    }

    if ('youtube' !== get_post_meta($lesson_a_id, '_kaanbal_video_provider', true) || 'dQw4w9WgXcQ' !== get_post_meta($lesson_a_id, '_kaanbal_video_source', true)) {
        throw new RuntimeException('YouTube video data was not normalized when saved.');
    }

    $missing_related_id = max($created_ids) + 100000;

    $_POST = array(
        'kaanbal_module_meta_nonce' => wp_create_nonce('kaanbal_save_module_meta'),
        'kaanbal_course_id'         => (string) $missing_related_id,
    );
    $meta_boxes->saveModule((int) $module_a_id);

    if ((string) $course_id !== get_post_meta($module_a_id, '_kaanbal_course_id', true)) {
        throw new RuntimeException('A nonexistent course relation overwrote the saved module metadata.');
    }

    $_POST = array(
        'kaanbal_lesson_meta_nonce' => wp_create_nonce('kaanbal_save_lesson_meta'),
        'kaanbal_module_id'         => (string) $missing_related_id,
        'kaanbal_video_provider'    => 'youtube',
        'kaanbal_video_source'      => 'dQw4w9WgXcQ',
    );
    $meta_boxes->saveLesson((int) $lesson_a_id);

    if ((string) $module_a_id !== get_post_meta($lesson_a_id, '_kaanbal_module_id', true)) {
        throw new RuntimeException('A nonexistent module relation overwrote the saved lesson metadata.');
    }

    $_POST = array(
        'kaanbal_lesson_meta_nonce' => wp_create_nonce('kaanbal_save_lesson_meta'),
        'kaanbal_module_id'         => (string) $course_id,
        'kaanbal_video_provider'    => 'unsupported',
        'kaanbal_video_source'      => 'invalid',
    );
    $meta_boxes->saveLesson((int) $lesson_a_id);

    if ((string) $module_a_id !== get_post_meta($lesson_a_id, '_kaanbal_module_id', true) || 'youtube' !== get_post_meta($lesson_a_id, '_kaanbal_video_provider', true)) {
        throw new RuntimeException('Invalid curriculum or video data overwrote the saved lesson metadata.');
    }

    $_POST = array(
        'kaanbal_lesson_meta_nonce' => wp_create_nonce('kaanbal_save_lesson_meta'),
        'kaanbal_module_id'         => (string) $module_a_id,
        'kaanbal_video_provider'    => '',
        'kaanbal_video_source'      => 'dQw4w9WgXcQ',
    );
    $meta_boxes->saveLesson((int) $lesson_a_id);

    if ('' !== get_post_meta($lesson_a_id, '_kaanbal_video_provider', true) || '' !== get_post_meta($lesson_a_id, '_kaanbal_video_source', true)) {
        throw new RuntimeException('Clearing a video provider did not clear the stored video source.');
    }

    echo "Courses and curriculum integration: PASS\n";
} finally {
    $_POST = $previous_post;
    wp_set_current_user($previous_user_id);

    foreach ($created_ids as $created_id) {
        wp_delete_post($created_id, true);
    }
}
