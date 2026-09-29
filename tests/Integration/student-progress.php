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
    $empty_course = $create_post('kaanbal_course', 'Progress empty course');
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
    $revoked = $user('revoked');
    $unenrolled = $user('unenrolled');
    global $wpdb;
    $table = $wpdb->prefix . 'kaanbal_enrollments';
    $progress_table = $wpdb->prefix . 'kaanbal_lesson_progress';
    $now = current_time('mysql', true);
    foreach (array($active, $other) as $user_id) {
        $wpdb->insert($table, array('user_id' => $user_id, 'course_id' => $course_a, 'status' => 'active', 'enrolled_at' => $now, 'created_at' => $now, 'updated_at' => $now));
    }
    $wpdb->insert($table, array('user_id' => $active, 'course_id' => $empty_course, 'status' => 'active', 'enrolled_at' => $now, 'created_at' => $now, 'updated_at' => $now));
    $wpdb->insert($table, array('user_id' => $revoked, 'course_id' => $course_a, 'status' => 'revoked', 'enrolled_at' => $now, 'revoked_at' => $now, 'created_at' => $now, 'updated_at' => $now));

    $repository = new Kaanbal\Progress\Infrastructure\LessonProgressRepository();
    $curriculum = new Kaanbal\Courses\Application\CurriculumService(new Kaanbal\Courses\Infrastructure\CurriculumRepository());
    $access = new Kaanbal\Access\Application\CourseAccessService(new Kaanbal\Enrollment\Infrastructure\EnrollmentRepository());
    $complete = new Kaanbal\Progress\Application\CompleteLessonService($access, $curriculum, $repository);
    $progress = new Kaanbal\Progress\Application\CourseProgressService($curriculum, $repository);

    if (0 !== $progress->forCourse($active, $empty_course)->percentage) {
        throw new RuntimeException('An empty course did not report zero progress.');
    }

    $router = new Kaanbal\Access\Presentation\Frontend\FrontendRouter();
    $course_post = get_post($course_a);
    $lesson_post = get_post($lesson_a_one);
    if (! $course_post instanceof WP_Post || ! $lesson_post instanceof WP_Post) {
        throw new RuntimeException('The progress fixture content could not be retrieved.');
    }
    $router->resolve($course_post->post_name, $lesson_post->post_name, $other);
    if (array() !== $repository->findCompletedLessonIds($other, array($lesson_a_one, $lesson_a_two))) {
        throw new RuntimeException('Opening a lesson wrote progress automatically.');
    }

    if (
        Kaanbal\Progress\Application\CompleteLessonResult::AccessDenied !== $complete->complete($unenrolled, $course_a, $lesson_a_one)
        || Kaanbal\Progress\Application\CompleteLessonResult::AccessDenied !== $complete->complete($revoked, $course_a, $lesson_a_one)
    ) {
        throw new RuntimeException('A user without an active enrollment could complete a lesson.');
    }

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

    if (Kaanbal\Progress\Application\CompleteLessonResult::InvalidLesson !== $complete->complete($active, $course_a, 999999999)) {
        throw new RuntimeException('A missing lesson could be completed.');
    }

    $lesson_a_three = $create_post('kaanbal_lesson', 'Progress lesson A three');
    update_post_meta($lesson_a_three, '_kaanbal_module_id', $module_a);
    if (33 !== $progress->forCourse($active, $course_a)->percentage) {
        throw new RuntimeException('Progress did not recalculate after a curriculum change.');
    }

    if (
        Kaanbal\Progress\Application\CompleteLessonResult::Completed !== $complete->complete($active, $course_a, $lesson_a_two)
        || 67 !== $progress->forCourse($active, $course_a)->percentage
    ) {
        throw new RuntimeException('A second lesson did not update the progress percentage.');
    }

    update_post_meta($lesson_a_two, '_kaanbal_module_id', 0);
    if (50 !== $progress->forCourse($active, $course_a)->percentage) {
        throw new RuntimeException('A completion for a removed lesson counted toward current progress.');
    }
    update_post_meta($lesson_a_two, '_kaanbal_module_id', $module_a);

    if (
        Kaanbal\Progress\Application\CompleteLessonResult::Completed !== $complete->complete($active, $course_a, $lesson_a_three)
        || 100 !== $progress->forCourse($active, $course_a)->percentage
    ) {
        throw new RuntimeException('Completing every lesson did not produce 100 percent.');
    }

    if ('active' !== $wpdb->get_var($wpdb->prepare('SELECT status FROM %i WHERE user_id = %d AND course_id = %d', $table, $active, $course_a))) {
        throw new RuntimeException('Lesson progress automatically completed the course enrollment.');
    }

    $endpoint = dirname(__DIR__) . '/Integration/support/progress-endpoint-request.php';
    $run_endpoint = static function (int $user_id, int $course_id, int $lesson_id, string $nonce) use ($endpoint, $wordpress_path): int {
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($endpoint)
            . ' --user=' . escapeshellarg((string) $user_id)
            . ' --course=' . escapeshellarg((string) $course_id)
            . ' --lesson=' . escapeshellarg((string) $lesson_id)
            . ' --nonce=' . escapeshellarg($nonce);
        $environment = array_merge(getenv(), array('KAANBAL_WP_PATH' => $wordpress_path));
        $process = proc_open($command, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, null, $environment);

        if (! is_resource($process)) {
            throw new RuntimeException('The progress endpoint test process could not be started.');
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    };

    if (
        0 !== $run_endpoint($other, $course_a, $lesson_a_one, 'valid')
        || 0 !== $run_endpoint($other, $course_a, $lesson_a_one, 'valid')
    ) {
        throw new RuntimeException('The endpoint did not accept an active user with a valid nonce.');
    }

    if (1 !== (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d AND lesson_id = %d', $progress_table, $other, $lesson_a_one))) {
        throw new RuntimeException('The endpoint did not write idempotently.');
    }

    foreach (
        array(
        array(0, $course_a, $lesson_a_two, 'missing'),
        array($other, $course_a, $lesson_a_two, 'missing'),
        array($other, $course_a, $lesson_a_two, 'invalid'),
        array($unenrolled, $course_a, $lesson_a_two, 'valid'),
        array($active, $course_b, $lesson_b, 'valid'),
        array($active, $course_a, 999999999, 'valid'),
        ) as $request
    ) {
        if (3 !== $run_endpoint(...$request)) {
            throw new RuntimeException('The endpoint did not reject an unauthorized completion request.');
        }
    }

    echo "Student progress integration: PASS\n";
} finally {
    if (isset($wpdb, $table, $progress_table) && $wpdb instanceof wpdb) {
        foreach ($created_users as $user_id) {
            $wpdb->delete($progress_table, array('user_id' => $user_id), array('%d'));
        }
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
