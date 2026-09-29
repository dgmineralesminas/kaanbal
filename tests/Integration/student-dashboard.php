<?php

declare(strict_types=1);

$wordpress_path = getenv('KAANBAL_WP_PATH');

if (! is_string($wordpress_path) || '' === $wordpress_path) {
    fwrite(STDERR, "KAANBAL_WP_PATH must point to the WordPress root.\n");
    exit(1);
}

require_once rtrim($wordpress_path, DIRECTORY_SEPARATOR) . '/wp-load.php';

$post_ids = array();
$user_ids = array();

try {
    require_once dirname(__DIR__, 2) . '/kaanbal.php';
    do_action('init');

    $rewrite_rules = get_option('rewrite_rules', array());
    if (! is_array($rewrite_rules) || ! array_key_exists('^mis-cursos/?$', $rewrite_rules)) {
        throw new RuntimeException('The student dashboard rewrite rule was not persisted.');
    }

    $suffix = substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
    $create_post = static function (string $type, string $title) use (&$post_ids, $suffix): int {
        $id = wp_insert_post(array('post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_name' => sanitize_title($title . '-' . $suffix)));

        if (! is_int($id) || $id <= 0) {
            throw new RuntimeException('The dashboard fixture post could not be created.');
        }

        $post_ids[] = $id;

        return $id;
    };
    $create_user = static function (string $label) use (&$user_ids, $suffix): int {
        $id = wp_insert_user(array('user_login' => 'dashboard-' . $label . '-' . $suffix, 'user_pass' => wp_generate_password(), 'user_email' => 'dashboard-' . $label . '-' . $suffix . '@example.test', 'role' => 'subscriber'));

        if (is_wp_error($id)) {
            throw new RuntimeException('The dashboard fixture user could not be created.');
        }

        $user_ids[] = (int) $id;

        return (int) $id;
    };
    $add_lessons = static function (int $course_id, int $count, string $label) use ($create_post): array {
        $module_id = $create_post('kaanbal_module', $label . ' module');
        update_post_meta($module_id, '_kaanbal_course_id', $course_id);
        $lessons = array();

        for ($number = 1; $number <= $count; $number++) {
            $lesson_id = $create_post('kaanbal_lesson', $label . ' lesson ' . $number);
            update_post_meta($lesson_id, '_kaanbal_module_id', $module_id);
            $lessons[] = $lesson_id;
        }

        return $lessons;
    };
    $add_quiz = static function (int $course_id, ?int $max_attempts) use ($create_post): int {
        $quiz_id = $create_post('kaanbal_quiz', 'Dashboard quiz ' . $course_id);
        update_post_meta($course_id, '_kaanbal_requires_final_quiz', '1');
        update_post_meta($quiz_id, '_kaanbal_course_id', $course_id);

        if (null !== $max_attempts) {
            update_post_meta($quiz_id, '_kaanbal_max_attempts', $max_attempts);
        }

        return $quiz_id;
    };

    $student = $create_user('student');
    $other = $create_user('other');
    $without_courses = $create_user('empty');
    $single = $create_user('single');
    $active = $create_post('kaanbal_course', 'Dashboard active course');
    $locked = $create_post('kaanbal_course', 'Dashboard locked course');
    $available = $create_post('kaanbal_course', 'Dashboard available course');
    $retry = $create_post('kaanbal_course', 'Dashboard retry course');
    $exhausted = $create_post('kaanbal_course', 'Dashboard exhausted course');
    $unlimited = $create_post('kaanbal_course', 'Dashboard unlimited course');
    $completed_certificate = $create_post('kaanbal_course', 'Dashboard certified course');
    $completed_plain = $create_post('kaanbal_course', 'Dashboard completed course');
    $revoked = $create_post('kaanbal_course', 'Dashboard revoked course');
    $other_course = $create_post('kaanbal_course', 'Dashboard private course');
    update_post_meta($active, '_kaanbal_instructor_name', 'Daniel');
    update_post_meta($active, '_kaanbal_duration', '2 horas');
    update_post_meta($completed_certificate, '_kaanbal_certificate_enabled', '1');
    $active_lessons = $add_lessons($active, 2, 'Active');
    $locked_lessons = $add_lessons($locked, 2, 'Locked');
    $available_lessons = $add_lessons($available, 1, 'Available');
    $retry_lessons = $add_lessons($retry, 1, 'Retry');
    $exhausted_lessons = $add_lessons($exhausted, 1, 'Exhausted');
    $unlimited_lessons = $add_lessons($unlimited, 1, 'Unlimited');
    $available_quiz = $add_quiz($available, 2);
    $retry_quiz = $add_quiz($retry, 2);
    $exhausted_quiz = $add_quiz($exhausted, 1);
    $unlimited_quiz = $add_quiz($unlimited, null);
    $certificate_quiz = $add_quiz($completed_certificate, null);
    $locked_quiz = $add_quiz($locked, 2);
    $late_quiz = $add_quiz($completed_plain, 2);

    global $wpdb;
    $enrollments_table = $wpdb->prefix . 'kaanbal_enrollments';
    $progress_table = $wpdb->prefix . 'kaanbal_lesson_progress';
    $attempts_table = $wpdb->prefix . 'kaanbal_quiz_attempts';
    $now = current_time('mysql', true);
    $enroll = static function (int $user_id, int $course_id, string $status, ?string $completed_at = null) use ($wpdb, $enrollments_table, $now): void {
        if (false === $wpdb->insert($enrollments_table, array('user_id' => $user_id, 'course_id' => $course_id, 'status' => $status, 'enrolled_at' => $now, 'completed_at' => $completed_at, 'revoked_at' => 'revoked' === $status ? $now : null, 'created_at' => $now, 'updated_at' => $now))) {
            throw new RuntimeException('The dashboard fixture enrollment could not be created.');
        }
    };
    $complete_lesson = static function (int $user_id, int $lesson_id) use ($wpdb, $progress_table, $now): void {
        $wpdb->insert($progress_table, array('user_id' => $user_id, 'lesson_id' => $lesson_id, 'completed_at' => $now, 'created_at' => $now, 'updated_at' => $now));
    };
    $record_attempt = static function (int $user_id, int $course_id, int $quiz_id, bool $passed) use ($wpdb, $attempts_table, $now): void {
        $count = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d AND quiz_id = %d', $attempts_table, $user_id, $quiz_id));
        $wpdb->insert($attempts_table, array('user_id' => $user_id, 'course_id' => $course_id, 'quiz_id' => $quiz_id, 'attempt_number' => $count + 1, 'status' => $passed ? 'passed' : 'failed', 'score' => $passed ? '100.00' : '0.00', 'passed' => $passed ? 1 : 0, 'started_at' => $now, 'completed_at' => $now, 'created_at' => $now, 'updated_at' => $now));
    };

    $enroll($student, $active, 'active');
    $enroll($student, $locked, 'active');
    $enroll($student, $available, 'active');
    $enroll($student, $retry, 'active');
    $enroll($student, $exhausted, 'active');
    $enroll($student, $unlimited, 'active');
    $enroll($student, $completed_certificate, 'completed', $now);
    $enroll($student, $completed_plain, 'completed', $now);
    $enroll($student, $revoked, 'revoked');
    $enroll($student, 999999999, 'active');
    $enroll($other, $other_course, 'active');
    $enroll($single, $retry, 'active');
    $complete_lesson($student, $active_lessons[0]);
    $complete_lesson($student, $available_lessons[0]);
    $complete_lesson($student, $retry_lessons[0]);
    $complete_lesson($student, $exhausted_lessons[0]);
    $complete_lesson($student, $unlimited_lessons[0]);
    $record_attempt($student, $retry, $retry_quiz, false);
    $record_attempt($student, $exhausted, $exhausted_quiz, false);
    $record_attempt($student, $completed_certificate, $certificate_quiz, true);

    $curriculum = new Kaanbal\Courses\Application\CurriculumService(new Kaanbal\Courses\Infrastructure\CurriculumRepository());
    $dashboard = new Kaanbal\Dashboard\Application\StudentDashboardQuery(new Kaanbal\Enrollment\Infrastructure\EnrollmentRepository(), new Kaanbal\Progress\Application\CourseProgressService($curriculum, new Kaanbal\Progress\Infrastructure\LessonProgressRepository()), new Kaanbal\Quiz\Infrastructure\QuizRepository(), new Kaanbal\Quiz\Infrastructure\QuizAttemptRepository());
    $router = new Kaanbal\Dashboard\Presentation\Frontend\DashboardRouter($dashboard, dirname(__DIR__, 2) . '/templates/student/');

    if (array('kaanbal_student_dashboard') !== $router->queryVars(array())) {
        throw new RuntimeException('The dashboard query var was not registered.');
    }

    $anonymous = $router->resolve(0);
    if (403 !== $anonymous['status'] || 'dashboard-access-denied' !== $anonymous['template'] || isset($anonymous['context']['dashboard']) || ! str_contains((string) ($anonymous['context']['login_url'] ?? ''), 'mis-cursos')) {
        throw new RuntimeException('The dashboard exposed academic data to an anonymous visitor.');
    }

    // Anonymous request through the real WordPress entry point, with a forged user_id.
    set_query_var(Kaanbal\Dashboard\Presentation\Frontend\DashboardRouter::QUERY_VAR, '1');
    wp_set_current_user(0);
    $_GET['user_id'] = (string) $student;
    $_REQUEST['user_id'] = (string) $student;
    $anonymous_template = $router->template('fallback.php');
    $anonymous_context = Kaanbal\Access\Presentation\Frontend\TemplateContext::all();

    if (! str_ends_with($anonymous_template, 'dashboard-access-denied.php') || isset($anonymous_context['dashboard'])) {
        throw new RuntimeException('A forged user_id exposed academic data to an anonymous visitor.');
    }

    $before = array(
        $wpdb->get_results($wpdb->prepare('SELECT course_id, status, completed_at FROM %i WHERE user_id = %d ORDER BY course_id', $enrollments_table, $student), ARRAY_A),
        $wpdb->get_results($wpdb->prepare('SELECT lesson_id FROM %i WHERE user_id = %d ORDER BY lesson_id', $progress_table, $student), ARRAY_A),
        $wpdb->get_results($wpdb->prepare('SELECT quiz_id, passed FROM %i WHERE user_id = %d ORDER BY quiz_id, attempt_number', $attempts_table, $student), ARRAY_A),
    );
    // Authenticated request through the real WordPress entry point, forging another student's id.
    wp_set_current_user($student);
    $_GET['user_id'] = (string) $other;
    $_REQUEST['user_id'] = (string) $other;
    $student_template = $router->template('fallback.php');
    $response = array(
        'status'   => str_ends_with($student_template, '/dashboard.php') ? 200 : 0,
        'template' => str_ends_with($student_template, '/dashboard.php') ? 'dashboard' : 'unexpected',
        'context'  => Kaanbal\Access\Presentation\Frontend\TemplateContext::all(),
    );
    $_GET = array();
    $_REQUEST = array();
    wp_set_current_user(0);
    set_query_var(Kaanbal\Dashboard\Presentation\Frontend\DashboardRouter::QUERY_VAR, '');
    $after = array(
        $wpdb->get_results($wpdb->prepare('SELECT course_id, status, completed_at FROM %i WHERE user_id = %d ORDER BY course_id', $enrollments_table, $student), ARRAY_A),
        $wpdb->get_results($wpdb->prepare('SELECT lesson_id FROM %i WHERE user_id = %d ORDER BY lesson_id', $progress_table, $student), ARRAY_A),
        $wpdb->get_results($wpdb->prepare('SELECT quiz_id, passed FROM %i WHERE user_id = %d ORDER BY quiz_id, attempt_number', $attempts_table, $student), ARRAY_A),
    );

    if ($before !== $after || 200 !== $response['status'] || 'dashboard' !== $response['template']) {
        throw new RuntimeException('Loading the dashboard was not read-only.');
    }

    $courses = $response['context']['dashboard']['courses'] ?? array();
    $by_id = array();
    foreach ($courses as $course) {
        $by_id[$course['course_id']] = $course;
    }

    if (8 !== count($by_id) || isset($by_id[$revoked]) || isset($by_id[$other_course]) || ! isset($by_id[$active], $by_id[$locked], $by_id[$available], $by_id[$retry], $by_id[$exhausted], $by_id[$unlimited], $by_id[$completed_certificate], $by_id[$completed_plain])) {
        throw new RuntimeException('The dashboard did not isolate relevant active and completed enrollments.');
    }

    if ('En curso' !== $by_id[$active]['status_label'] || 1 !== $by_id[$active]['progress']->completed_lessons || 2 !== $by_id[$active]['progress']->total_lessons || 50 !== $by_id[$active]['progress']->percentage || 'not_required' !== $by_id[$active]['quiz']['state'] || 'Daniel' !== $by_id[$active]['instructor'] || '2 horas' !== $by_id[$active]['duration']) {
        throw new RuntimeException('The active course dashboard data is incorrect.');
    }

    if ('locked' !== $by_id[$locked]['quiz']['state'] || 'available' !== $by_id[$available]['quiz']['state'] || 2 !== $by_id[$available]['quiz']['attempts_remaining'] || 'failed_can_retry' !== $by_id[$retry]['quiz']['state'] || 1 !== $by_id[$retry]['quiz']['attempts_remaining'] || 'no_attempts_left' !== $by_id[$exhausted]['quiz']['state'] || 0 !== $by_id[$exhausted]['quiz']['attempts_remaining'] || 'available' !== $by_id[$unlimited]['quiz']['state'] || $by_id[$unlimited]['quiz']['attempts_limited'] || null !== $by_id[$unlimited]['quiz']['attempts_remaining']) {
        throw new RuntimeException('The quiz dashboard states or remaining attempts are incorrect.');
    }

    if ('Aprobado' !== $by_id[$completed_certificate]['status_label'] || 'passed' !== $by_id[$completed_certificate]['quiz']['state'] || ! $by_id[$completed_certificate]['quiz']['visible'] || $by_id[$completed_certificate]['quiz']['show_attempts'] || ! $by_id[$completed_certificate]['certificate_enabled'] || ! $by_id[$completed_certificate]['show_certificate'] || 'Aprobado' !== $by_id[$completed_plain]['status_label'] || $by_id[$completed_plain]['certificate_enabled'] || $by_id[$completed_plain]['show_certificate']) {
        throw new RuntimeException('Completed-course dashboard data is incorrect.');
    }

    // A quiz enabled after approval must not appear as pending on an approved course (RB-004, EC-007).
    if (! $by_id[$completed_plain]['quiz']['required'] || $by_id[$completed_plain]['quiz']['visible']) {
        throw new RuntimeException('An approved course advertised a pending quiz.');
    }

    // Attempts are only shown while the quiz can still be presented.
    if (! $by_id[$available]['quiz']['show_attempts'] || ! $by_id[$retry]['quiz']['show_attempts'] || $by_id[$exhausted]['quiz']['show_attempts'] || $by_id[$locked]['quiz']['show_attempts']) {
        throw new RuntimeException('The dashboard shows attempts for a quiz that cannot be presented.');
    }

    // AC-025: loading many courses must not add queries per course.
    $count_queries = static function (int $user_id) use ($dashboard, $wpdb): int {
        wp_cache_flush();
        $before_queries = $wpdb->num_queries;
        $dashboard->forUser($user_id);

        return $wpdb->num_queries - $before_queries;
    };
    $single_queries = $count_queries($single);
    $many_queries = $count_queries($student);

    fwrite(STDOUT, sprintf("Dashboard queries: 1 course = %d, 8 courses = %d\n", $single_queries, $many_queries));
    if ($many_queries - $single_queries > 3) {
        throw new RuntimeException(sprintf('The dashboard issued per-course queries: 1 course = %d queries, 8 courses = %d queries.', $single_queries, $many_queries));
    }

    Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($response['context']);
    ob_start();
    require dirname(__DIR__, 2) . '/templates/student/parts/dashboard-content.php';
    $markup = (string) ob_get_clean();

    if (! str_contains($markup, 'Mis cursos') || ! str_contains($markup, 'Dashboard active course') || ! str_contains($markup, '50%') || ! str_contains($markup, 'Continuar curso') || ! str_contains($markup, 'Ver curso') || ! str_contains($markup, 'Completa las lecciones') || ! str_contains($markup, 'Tu evaluación final está disponible') || ! str_contains($markup, 'Intentos agotados') || ! str_contains($markup, 'Intentos ilimitados') || ! str_contains($markup, 'Evaluación final aprobada') || ! str_contains($markup, 'Has aprobado este curso. En breve nos estaremos comunicando contigo para hacerte llegar tu certificado.') || str_contains($markup, '0 intentos restantes') || 1 !== substr_count($markup, 'data-quiz-state="passed"') || str_contains($markup, 'certificado.pdf') || str_contains($markup, 'Dashboard private course') || str_contains($markup, 'Dashboard revoked course') || str_contains($markup, 'Descargar certificado')) {
        throw new RuntimeException('The dashboard template did not render the expected student-facing states.');
    }

    $empty = $router->resolve($without_courses);
    Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($empty['context']);
    ob_start();
    require dirname(__DIR__, 2) . '/templates/student/parts/dashboard-content.php';
    $empty_markup = (string) ob_get_clean();

    if (200 !== $empty['status'] || ! str_contains($empty_markup, 'Aún no tienes cursos disponibles.')) {
        throw new RuntimeException('The dashboard empty state did not render.');
    }

    echo "Student dashboard integration: PASS\n";
} finally {
    if (isset($wpdb) && $wpdb instanceof wpdb) {
        foreach ($user_ids as $user_id) {
            $wpdb->delete($wpdb->prefix . 'kaanbal_lesson_progress', array('user_id' => $user_id));
            $wpdb->delete($wpdb->prefix . 'kaanbal_quiz_attempts', array('user_id' => $user_id));
            $wpdb->delete($wpdb->prefix . 'kaanbal_enrollments', array('user_id' => $user_id));
        }
    }

    foreach ($post_ids as $post_id) {
        wp_delete_post($post_id, true);
    }

    if (array() !== $user_ids) {
        require_once ABSPATH . '/wp-admin/includes/user.php';
        foreach ($user_ids as $user_id) {
            wp_delete_user($user_id);
        }
    }
}
