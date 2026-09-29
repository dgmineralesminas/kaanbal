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

$plugin_file = dirname(__DIR__, 2) . '/kaanbal.php';
$created_ids = array();
$created_users = array();
$enrollment_user_ids = array();

try {
    require_once $plugin_file;
    do_action('init');

    $suffix = (string) wp_generate_uuid4();
    $create_post = static function (string $type, string $title, int $menu_order = 0) use (&$created_ids, $suffix): int {
        $post_id = wp_insert_post(
            array(
                'post_type'    => $type,
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_name'    => sanitize_title($title . '-' . $suffix),
                'post_content' => '<p>Protected player content.</p>',
                'menu_order'   => $menu_order,
            )
        );

        if (! is_int($post_id) || $post_id <= 0) {
            throw new RuntimeException('The player fixture post could not be created.');
        }

        $created_ids[] = $post_id;

        return $post_id;
    };
    $create_user = static function (string $label) use (&$created_users, $suffix): int {
        $user_id = wp_insert_user(
            array(
                'user_login' => 'kaanbal-player-' . $label . '-' . substr($suffix, 0, 8),
                'user_pass'  => wp_generate_password(24, true, true),
                'user_email' => 'kaanbal-player-' . $label . '-' . substr($suffix, 0, 8) . '@example.test',
                'role'       => 'subscriber',
            )
        );

        if (is_wp_error($user_id)) {
            throw new RuntimeException('The player fixture user could not be created.');
        }

        $created_users[] = (int) $user_id;

        return (int) $user_id;
    };

    $course_a_id = $create_post('kaanbal_course', 'Player course A');
    $course_b_id = $create_post('kaanbal_course', 'Player course B');
    $course_without_modules_id = $create_post('kaanbal_course', 'Player course without modules');
    $module_a_id = $create_post('kaanbal_module', 'Player module A');
    $module_empty_id = $create_post('kaanbal_module', 'Player empty module', 1);
    $module_b_id = $create_post('kaanbal_module', 'Player module B');
    $lesson_a_one_id = $create_post('kaanbal_lesson', 'Player lesson A one');
    $lesson_a_two_id = $create_post('kaanbal_lesson', 'Player lesson A two', 1);
    $lesson_b_id = $create_post('kaanbal_lesson', 'Player lesson B');

    update_post_meta($module_a_id, '_kaanbal_course_id', $course_a_id);
    update_post_meta($module_empty_id, '_kaanbal_course_id', $course_a_id);
    update_post_meta($module_b_id, '_kaanbal_course_id', $course_b_id);
    update_post_meta($lesson_a_one_id, '_kaanbal_module_id', $module_a_id);
    update_post_meta($lesson_a_two_id, '_kaanbal_module_id', $module_a_id);
    update_post_meta($lesson_b_id, '_kaanbal_module_id', $module_b_id);
    update_post_meta($lesson_a_one_id, '_kaanbal_video_provider', 'youtube');
    update_post_meta($lesson_a_one_id, '_kaanbal_video_source', 'dQw4w9WgXcQ');

    $active_user = $create_user('active');
    $completed_user = $create_user('completed');
    $revoked_user = $create_user('revoked');
    $unenrolled_user = $create_user('unenrolled');

    global $wpdb;

    if (! $wpdb instanceof wpdb) {
        throw new RuntimeException('WordPress database access is required for the player fixture.');
    }

    $enrollments_table = $wpdb->prefix . 'kaanbal_enrollments';
    $now = current_time('mysql', true);
    $insert_enrollment = static function (int $user_id, int $course_id, string $status) use ($wpdb, $enrollments_table, $now, &$enrollment_user_ids): void {
        $inserted = $wpdb->insert(
            $enrollments_table,
            array(
                'user_id'     => $user_id,
                'course_id'   => $course_id,
                'status'      => $status,
                'enrolled_at' => $now,
                'revoked_at'  => 'revoked' === $status ? $now : null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            throw new RuntimeException('The player fixture enrollment could not be created.');
        }

        $enrollment_user_ids[] = $user_id;
    };

    $insert_enrollment($active_user, $course_a_id, 'active');
    $insert_enrollment($active_user, $course_without_modules_id, 'active');
    $insert_enrollment($completed_user, $course_b_id, 'completed');
    $insert_enrollment($revoked_user, $course_a_id, 'revoked');

    $course_a = get_post($course_a_id);
    $course_b = get_post($course_b_id);
    $course_without_modules = get_post($course_without_modules_id);
    $lesson_a_one = get_post($lesson_a_one_id);
    $lesson_a_two = get_post($lesson_a_two_id);
    $lesson_b = get_post($lesson_b_id);

    if (! $course_a instanceof WP_Post || ! $course_b instanceof WP_Post || ! $course_without_modules instanceof WP_Post || ! $lesson_a_one instanceof WP_Post || ! $lesson_a_two instanceof WP_Post || ! $lesson_b instanceof WP_Post) {
        throw new RuntimeException('The player fixture content could not be retrieved.');
    }

    $router = new Kaanbal\Access\Presentation\Frontend\FrontendRouter();

    if (array('kaanbal_course', 'kaanbal_lesson') !== $router->queryVars(array())) {
        throw new RuntimeException('The player query vars were not registered.');
    }

    $course_response = $router->resolve($course_a->post_name, null, $active_user);

    if (200 !== $course_response['status'] || 'course' !== $course_response['template'] || 2 !== count($course_response['context']['curriculum']['modules'])) {
        throw new RuntimeException('An active enrollment could not open the ordered course view.');
    }

    if (array($module_a_id, $module_empty_id) !== array_map(static fn (array $module_item): int => $module_item['module']->ID, $course_response['context']['curriculum']['modules'])) {
        throw new RuntimeException('The course modules are not ordered as defined by the curriculum.');
    }

    Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($course_response['context']);
    ob_start();
    require dirname(__DIR__, 2) . '/templates/frontend/course.php';
    $course_markup = (string) ob_get_clean();

    if (! str_contains($course_markup, 'Player course A') || ! str_contains($course_markup, 'Player empty module') || ! str_contains($course_markup, 'Comienza tu curso') || ! str_contains($course_markup, '0 de 2 lecciones completadas') || ! str_contains($course_markup, 'role="progressbar"') || ! str_contains($course_markup, 'Reproducir')) {
        throw new RuntimeException('The authorized course template did not render course and empty-module content.');
    }

    $course_without_modules_response = $router->resolve($course_without_modules->post_name, null, $active_user);

    if (200 !== $course_without_modules_response['status'] || 'course' !== $course_without_modules_response['template'] || array() !== $course_without_modules_response['context']['curriculum']['modules']) {
        throw new RuntimeException('An active enrollment could not open a published course without modules.');
    }

    Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($course_without_modules_response['context']);
    ob_start();
    require dirname(__DIR__, 2) . '/templates/frontend/course.php';
    $course_without_modules_markup = (string) ob_get_clean();

    if (! str_contains($course_without_modules_markup, 'Player course without modules')) {
        throw new RuntimeException('The course template could not render a published course without modules.');
    }

    $lesson_response = $router->resolve($course_a->post_name, $lesson_a_one->post_name, $active_user);

    if (200 !== $lesson_response['status'] || 'lesson' !== $lesson_response['template'] || 2 !== count($lesson_response['context']['curriculum']['modules']) || $lesson_a_two_id !== $lesson_response['context']['navigation']['next']->ID || null !== $lesson_response['context']['navigation']['previous']) {
        throw new RuntimeException('The authorized lesson view or navigation is incorrect.');
    }

    Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($lesson_response['context']);
    ob_start();
    require dirname(__DIR__, 2) . '/templates/frontend/lesson.php';
    $lesson_markup = (string) ob_get_clean();

    if (! str_contains($lesson_markup, 'Player lesson A one') || ! str_contains($lesson_markup, 'youtube-nocookie.com/embed/dQw4w9WgXcQ') || ! str_contains($lesson_markup, 'aria-current="page"') || ! str_contains($lesson_markup, 'Player lesson A two') || ! str_contains($lesson_markup, 'name="action" value="kaanbal_complete_lesson"') || ! str_contains($lesson_markup, 'name="_kaanbal_nonce"')) {
        throw new RuntimeException('The authorized lesson template did not render its protected content and video player.');
    }

    $progress_table = $wpdb->prefix . 'kaanbal_lesson_progress';
    $wpdb->insert(
        $progress_table,
        array(
            'user_id'      => $active_user,
            'lesson_id'    => $lesson_a_one_id,
            'completed_at' => $now,
            'created_at'   => $now,
            'updated_at'   => $now,
        )
    );
    $completed_course_response = $router->resolve($course_a->post_name, null, $active_user);
    Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($completed_course_response['context']);
    ob_start();
    require dirname(__DIR__, 2) . '/templates/frontend/course.php';
    $completed_course_markup = (string) ob_get_clean();

    if (! str_contains($completed_course_markup, '1 de 2 lecciones completadas') || ! str_contains($completed_course_markup, 'aria-valuenow="50"') || ! str_contains($completed_course_markup, 'is-completed') || ! str_contains($completed_course_markup, 'Completada') || ! str_contains($completed_course_markup, 'Reproducir')) {
        throw new RuntimeException('The course template did not distinguish completed and pending lessons.');
    }

    $completed_lesson_response = $router->resolve($course_a->post_name, $lesson_a_one->post_name, $active_user);
    Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($completed_lesson_response['context']);
    ob_start();
    require dirname(__DIR__, 2) . '/templates/frontend/lesson.php';
    $completed_lesson_markup = (string) ob_get_clean();

    if (! str_contains($completed_lesson_markup, 'Lección completada') || str_contains($completed_lesson_markup, 'name="action" value="kaanbal_complete_lesson"')) {
        throw new RuntimeException('The lesson template did not render the completed state.');
    }

    $lesson_two_response = $router->resolve($course_a->post_name, $lesson_a_two->post_name, $active_user);

    if (200 !== $lesson_two_response['status'] || 'lesson' !== $lesson_two_response['template']) {
        throw new RuntimeException('An active enrollment could not freely open the second lesson.');
    }

    Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($lesson_two_response['context']);
    ob_start();
    require dirname(__DIR__, 2) . '/templates/frontend/lesson.php';
    $lesson_two_markup = (string) ob_get_clean();

    if (! str_contains($lesson_two_markup, 'Player lesson A two') || ! str_contains($lesson_two_markup, 'Player module A') || ! str_contains($lesson_two_markup, 'Protected player content.')) {
        throw new RuntimeException('The second lesson template did not render its title, module and content.');
    }

    $lesson_without_video_response = $router->resolve($course_b->post_name, $lesson_b->post_name, $completed_user);

    if (200 !== $lesson_without_video_response['status'] || 'lesson' !== $lesson_without_video_response['template']) {
        throw new RuntimeException('A completed enrollment could not open a lesson without video.');
    }

    Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($lesson_without_video_response['context']);
    ob_start();
    require dirname(__DIR__, 2) . '/templates/frontend/lesson.php';
    $lesson_without_video_markup = (string) ob_get_clean();

    if (! str_contains($lesson_without_video_markup, 'Player lesson B') || str_contains($lesson_without_video_markup, 'kaanbal-player')) {
        throw new RuntimeException('The lesson template did not render a text-only lesson correctly.');
    }

    $before = $wpdb->get_row(
        $wpdb->prepare('SELECT status, enrolled_at, revoked_at, updated_at FROM %i WHERE user_id = %d AND course_id = %d', $enrollments_table, $active_user, $course_a_id),
        ARRAY_A
    );
    $router->resolve($course_a->post_name, $lesson_a_two->post_name, $active_user);
    $after = $wpdb->get_row(
        $wpdb->prepare('SELECT status, enrolled_at, revoked_at, updated_at FROM %i WHERE user_id = %d AND course_id = %d', $enrollments_table, $active_user, $course_a_id),
        ARRAY_A
    );

    if ($before !== $after) {
        throw new RuntimeException('Opening a course or lesson wrote enrollment data.');
    }

    if (200 !== $router->resolve($course_b->post_name, null, $completed_user)['status']) {
        throw new RuntimeException('A completed enrollment did not retain access.');
    }

    foreach (array($revoked_user, $unenrolled_user, 0) as $denied_user) {
        $response = $router->resolve($course_a->post_name, null, $denied_user);

        if (403 !== $response['status'] || 'access-denied' !== $response['template'] || array_key_exists('curriculum', $response['context'])) {
            throw new RuntimeException('Unauthorized access revealed protected course content.');
        }

        $lesson_response = $router->resolve($course_a->post_name, $lesson_a_one->post_name, $denied_user);

        if (403 !== $lesson_response['status'] || 'access-denied' !== $lesson_response['template'] || array_key_exists('lesson', $lesson_response['context']) || array_key_exists('curriculum', $lesson_response['context']) || array_key_exists('course', $lesson_response['context'])) {
            throw new RuntimeException('Unauthorized lesson access revealed protected content.');
        }
    }

    $cross_enrollment_response = $router->resolve($course_b->post_name, $lesson_b->post_name, $active_user);

    if (403 !== $cross_enrollment_response['status'] || 'access-denied' !== $cross_enrollment_response['template']) {
        throw new RuntimeException('An enrollment in another course granted access to a protected lesson.');
    }

    if (404 !== $router->resolve($course_a->post_name, $lesson_b->post_name, $active_user)['status']) {
        throw new RuntimeException('A forged Course/Lesson combination was not rejected.');
    }

    if (404 !== $router->resolve('missing-course', null, $active_user)['status'] || 404 !== $router->resolve($course_a->post_name, 'missing-lesson', $active_user)['status']) {
        throw new RuntimeException('Missing protected content was not handled safely.');
    }

    echo "Course access and player integration: PASS\n";
} finally {
    if (isset($wpdb) && $wpdb instanceof wpdb && isset($enrollments_table)) {
        if (isset($progress_table)) {
            $wpdb->delete($progress_table, array('user_id' => $active_user), array('%d'));
        }
        foreach (array_unique($enrollment_user_ids) as $user_id) {
            $wpdb->delete($enrollments_table, array('user_id' => $user_id), array('%d'));
        }
    }

    foreach ($created_ids as $created_id) {
        wp_delete_post($created_id, true);
    }

    if (array() !== $created_users) {
        require_once ABSPATH . 'wp-admin/includes/user.php';

        foreach ($created_users as $created_user) {
            wp_delete_user($created_user);
        }
    }
}
