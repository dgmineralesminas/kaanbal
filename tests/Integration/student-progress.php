<?php

declare(strict_types=1);

$wordpress_path = getenv('KAANBAL_WP_PATH');

if (! is_string($wordpress_path) || '' === $wordpress_path) {
    fwrite(STDERR, "KAANBAL_WP_PATH must point to the WordPress root.\n");
    exit(1);
}

require_once rtrim($wordpress_path, DIRECTORY_SEPARATOR) . '/wp-load.php';

$created_ids = array();
$created_users = array();

try {
    require_once dirname(__DIR__, 2) . '/kaanbal.php';
    do_action('init');

    $suffix = (string) wp_generate_uuid4();
    $create_post = static function (string $type, string $title) use (&$created_ids, $suffix): int {
        $id = wp_insert_post(array('post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_name' => sanitize_title($title . '-' . $suffix)));
        $created_ids[] = (int) $id;
        return (int) $id;
    };
    $user = static function (string $label) use (&$created_users, $suffix): int {
        $id = wp_insert_user(array('user_login' => 'progress-' . $label . '-' . substr($suffix, 0, 8), 'user_pass' => wp_generate_password(), 'user_email' => 'progress-' . $label . '-' . substr($suffix, 0, 8) . '@example.test', 'role' => 'subscriber'));
        if (is_wp_error($id)) {
            throw new RuntimeException('The progress fixture user could not be created.');
        }
        $created_users[] = (int) $id;
        return (int) $id;
    };

    $course_a = $create_post('kaanbal_course', 'Progress course A');
    $course_b = $create_post('kaanbal_course', 'Progress course B');
    $module_a = $create_post('kaanbal_module', 'Progress module A');
    $module_b = $create_post('kaanbal_module', 'Progress module B');
    $lesson_a_one = $create_post('kaanbal_lesson', 'Progress lesson A one');
    $lesson_a_two = $create_post('kaanbal_lesson', 'Progress lesson A two');
    $lesson_b = $create_post('kaanbal_lesson', 'Progress lesson B');
    update_post_meta($module_a, '_kaanbal_course_id', $course_a);
    update_post_meta($module_b, '_kaanbal_course_id', $course_b);
    update_post_meta($lesson_a_one, '_kaanbal_module_id', $module_a);
    update_post_meta($lesson_a_two, '_kaanbal_module_id', $module_a);
    update_post_meta($lesson_b, '_kaanbal_module_id', $module_b);

    $active = $user('active');
    $other = $user('other');
    global $wpdb;
    $table = $wpdb->prefix . 'kaanbal_enrollments';
    $now = current_time('mysql', true);
    foreach (array($active, $other) as $user_id) {
        $wpdb->insert($table, array('user_id' => $user_id, 'course_id' => $course_a, 'status' => 'active', 'enrolled_at' => $now, 'created_at' => $now, 'updated_at' => $now));
    }

    $repository = new Kaanbal\Progress\Infrastructure\LessonProgressRepository();
    $curriculum = new Kaanbal\Courses\Application\CurriculumService(new Kaanbal\Courses\Infrastructure\CurriculumRepository());
    $access = new Kaanbal\Access\Application\CourseAccessService(new Kaanbal\Enrollment\Infrastructure\EnrollmentRepository());
    $complete = new Kaanbal\Progress\Application\CompleteLessonService($access, $curriculum, $repository);
    $progress = new Kaanbal\Progress\Application\CourseProgressService($curriculum, $repository);

    if (Kaanbal\Progress\Application\CompleteLessonResult::Completed !== $complete->complete($active, $course_a, $lesson_a_one) || Kaanbal\Progress\Application\CompleteLessonResult::AlreadyCompleted !== $complete->complete($active, $course_a, $lesson_a_one)) {
        throw new RuntimeException('Lesson completion is not idempotent.');
    }

    $active_progress = $progress->forCourse($active, $course_a);
    $other_progress = $progress->forCourse($other, $course_a);
    if (2 !== $active_progress->total_lessons || 1 !== $active_progress->completed_lessons || 50 !== $active_progress->percentage || 0 !== $other_progress->completed_lessons) {
        throw new RuntimeException('Course progress is not correctly derived or isolated by user.');
    }

    if (Kaanbal\Progress\Application\CompleteLessonResult::CourseMismatch !== $complete->complete($active, $course_a, $lesson_b)) {
        throw new RuntimeException('A lesson from another course could be completed.');
    }

    $lesson_a_three = $create_post('kaanbal_lesson', 'Progress lesson A three');
    update_post_meta($lesson_a_three, '_kaanbal_module_id', $module_a);
    if (33 !== $progress->forCourse($active, $course_a)->percentage) {
        throw new RuntimeException('Progress did not recalculate after a curriculum change.');
    }

    echo "Student progress integration: PASS\n";
} finally {
    if (isset($wpdb, $table) && $wpdb instanceof wpdb) {
        foreach ($created_users as $user_id) {
            $wpdb->delete($table, array('user_id' => $user_id));
        }
    }
    foreach ($created_ids as $id) {
        wp_delete_post($id, true);
    }
    if (array() !== $created_users) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ($created_users as $id) {
            wp_delete_user($id);
        }
    }
}
