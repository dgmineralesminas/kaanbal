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
$original_timezone_string = get_option('timezone_string');
$original_gmt_offset = get_option('gmt_offset');

try {
    require_once dirname(__DIR__, 2) . '/kaanbal.php';
    do_action('init');
    update_option('timezone_string', 'America/Mexico_City');
    update_option('gmt_offset', -6);

    $suffix = substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
    $create_post = static function (string $type, string $title) use (&$post_ids, $suffix): int {
        $post_id = wp_insert_post(
            array(
                'post_type'   => $type,
                'post_status' => 'publish',
                'post_title'  => $title,
                'post_name'   => sanitize_title($title . '-' . $suffix),
            )
        );

        if (! is_int($post_id) || $post_id <= 0) {
            throw new RuntimeException('The reporting fixture post could not be created.');
        }

        $post_ids[] = $post_id;

        return $post_id;
    };
    $create_user = static function (string $label, string $role = 'subscriber', ?string $email = null) use (&$user_ids, $suffix): int {
        $user_id = wp_insert_user(
            array(
                'user_login' => 'reporting-' . $label . '-' . $suffix,
                'user_pass'  => wp_generate_password(),
                'user_email' => $email ?? 'reporting-' . $label . '-' . $suffix . '@example.test',
                'role'       => $role,
            )
        );

        if (is_wp_error($user_id)) {
            throw new RuntimeException('The reporting fixture user could not be created.');
        }

        $user_ids[] = (int) $user_id;

        return (int) $user_id;
    };
    $create_lessons = static function (int $course_id, string $label, int $count) use ($create_post): array {
        $module_id = $create_post('kaanbal_module', $label . ' module');
        update_post_meta($module_id, '_kaanbal_course_id', $course_id);
        $lesson_ids = array();

        for ($position = 1; $position <= $count; $position++) {
            $lesson_id = $create_post('kaanbal_lesson', $label . ' lesson ' . $position);
            update_post_meta($lesson_id, '_kaanbal_module_id', $module_id);
            $lesson_ids[] = $lesson_id;
        }

        return $lesson_ids;
    };

    $administrator = $create_user('administrator', 'administrator');
    $active_full = $create_user('active-full');
    $active_partial = $create_user('active-partial');
    $completed = $create_user('completed');
    $revoked = $create_user('revoked');
    $failed = $create_user('failed');
    $exhausted = $create_user('exhausted');
    $no_quiz_student = $create_user('no-quiz');
    $unlimited_student = $create_user('unlimited');
    $course = $create_post('kaanbal_course', 'Reporting course');
    $without_quiz = $create_post('kaanbal_course', 'Reporting course without quiz');
    $unlimited_course = $create_post('kaanbal_course', 'Reporting unlimited course');
    $pagination_course = $create_post('kaanbal_course', 'Reporting pagination course');
    $lesson_ids = $create_lessons($course, 'Reporting', 2);
    $no_quiz_lesson_ids = $create_lessons($without_quiz, 'No quiz reporting', 1);
    $unlimited_lesson_ids = $create_lessons($unlimited_course, 'Unlimited reporting', 1);
    $create_lessons($pagination_course, 'Pagination reporting', 1);
    $quiz = $create_post('kaanbal_quiz', 'Reporting quiz');
    $unlimited_quiz = $create_post('kaanbal_quiz', 'Reporting unlimited quiz');
    update_post_meta($course, '_kaanbal_requires_final_quiz', '1');
    update_post_meta($course, '_kaanbal_certificate_enabled', '1');
    update_post_meta($quiz, '_kaanbal_course_id', $course);
    update_post_meta($quiz, '_kaanbal_max_attempts', '3');
    update_post_meta($unlimited_course, '_kaanbal_requires_final_quiz', '1');
    update_post_meta($unlimited_quiz, '_kaanbal_course_id', $unlimited_course);
    $questions = new Kaanbal\Quiz\Infrastructure\QuestionRepository();
    $question_id = $questions->create($quiz, 'Reporting question', 1);
    $answers = new Kaanbal\Quiz\Infrastructure\AnswerRepository();
    $answers->create($question_id, 'Reporting answer', true, 1);
    $answers->create($question_id, 'Reporting incorrect answer', false, 2);
    $unlimited_question_id = $questions->create($unlimited_quiz, 'Unlimited reporting question', 1);
    $answers->create($unlimited_question_id, 'Unlimited reporting answer', true, 1);
    $answers->create($unlimited_question_id, 'Unlimited reporting incorrect answer', false, 2);

    global $wpdb;
    $enrollments_table = $wpdb->prefix . 'kaanbal_enrollments';
    $progress_table = $wpdb->prefix . 'kaanbal_lesson_progress';
    $attempts_table = $wpdb->prefix . 'kaanbal_quiz_attempts';
    $now = current_time('mysql', true);
    $enroll = static function (int $user_id, int $course_id, string $status, ?string $completed_at = null) use ($wpdb, $enrollments_table, $now): void {
        if (
            false === $wpdb->insert(
                $enrollments_table,
                array(
                    'user_id'      => $user_id,
                    'course_id'    => $course_id,
                    'status'       => $status,
                    'enrolled_at'  => $now,
                    'completed_at' => $completed_at,
                    'revoked_at'   => 'revoked' === $status ? $now : null,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                )
            )
        ) {
            throw new RuntimeException('The reporting fixture enrollment could not be created.');
        }
    };
    $complete_lesson = static function (int $user_id, int $lesson_id) use ($wpdb, $progress_table, $now): void {
        if (false === $wpdb->insert($progress_table, array('user_id' => $user_id, 'lesson_id' => $lesson_id, 'completed_at' => $now, 'created_at' => $now, 'updated_at' => $now))) {
            throw new RuntimeException('The reporting fixture progress could not be created.');
        }
    };
    $attempt = static function (int $user_id, int $course_id, int $quiz_id, bool $passed, int $attempt_number = 1) use ($wpdb, $attempts_table, $now): void {
        if (false === $wpdb->insert($attempts_table, array('user_id' => $user_id, 'course_id' => $course_id, 'quiz_id' => $quiz_id, 'attempt_number' => $attempt_number, 'status' => $passed ? 'passed' : 'failed', 'score' => $passed ? '100.00' : '0.00', 'passed' => $passed ? 1 : 0, 'started_at' => $now, 'completed_at' => $now, 'created_at' => $now, 'updated_at' => $now))) {
            throw new RuntimeException('The reporting fixture attempt could not be created.');
        }
    };

    $enroll($active_full, $course, 'active');
    $enroll($active_partial, $course, 'active');
    $enroll($completed, $course, 'completed', $now);
    $enroll($revoked, $course, 'revoked');
    $enroll($failed, $course, 'active');
    $enroll($exhausted, $course, 'active');
    $enroll($no_quiz_student, $without_quiz, 'active');
    $enroll($unlimited_student, $unlimited_course, 'active');
    $complete_lesson($active_full, $lesson_ids[0]);
    $complete_lesson($active_full, $lesson_ids[1]);
    $complete_lesson($active_partial, $lesson_ids[0]);
    $complete_lesson($completed, $lesson_ids[0]);
    $complete_lesson($completed, $lesson_ids[1]);
    $complete_lesson($failed, $lesson_ids[0]);
    $complete_lesson($failed, $lesson_ids[1]);
    $complete_lesson($exhausted, $lesson_ids[0]);
    $complete_lesson($exhausted, $lesson_ids[1]);
    $complete_lesson($no_quiz_student, $no_quiz_lesson_ids[0]);
    $complete_lesson($unlimited_student, $unlimited_lesson_ids[0]);
    $attempt($completed, $course, $quiz, true);
    $attempt($failed, $course, $quiz, false);
    $attempt($exhausted, $course, $quiz, false, 1);
    $attempt($exhausted, $course, $quiz, false, 2);
    $attempt($exhausted, $course, $quiz, false, 3);
    $attempt($unlimited_student, $unlimited_course, $unlimited_quiz, false, 1);
    $attempt($unlimited_student, $unlimited_course, $unlimited_quiz, false, 2);

    for ($number = 1; $number <= 26; $number++) {
        $page_user = $create_user('page-' . $number, 'subscriber', 'a+b-page-' . $number . '-' . $suffix . '@example.test');
        $enroll($page_user, $pagination_course, 'active');
    }

    $curriculum = new Kaanbal\Courses\Application\CurriculumService(new Kaanbal\Courses\Infrastructure\CurriculumRepository());
    $reporting = new Kaanbal\Reporting\Application\CourseReportingQuery(
        new Kaanbal\Enrollment\Infrastructure\EnrollmentRepository(),
        new Kaanbal\Progress\Application\CourseProgressService($curriculum, new Kaanbal\Progress\Infrastructure\LessonProgressRepository()),
        new Kaanbal\Quiz\Infrastructure\QuizRepository(),
        new Kaanbal\Quiz\Infrastructure\QuizAttemptRepository(),
        new Kaanbal\Quiz\Application\QuizValidityService(new Kaanbal\Quiz\Infrastructure\QuestionRepository()),
    );
    $page = new Kaanbal\Reporting\Presentation\Admin\CourseReportsPage($reporting);
    wp_set_current_user($administrator);
    do_action('admin_menu');
    global $submenu;
    if (! isset($submenu['edit.php?post_type=kaanbal_course'])) {
        throw new RuntimeException('The reporting admin menu was not registered.');
    }

    wp_set_current_user($active_full);
    if ($page->canAccess()) {
        throw new RuntimeException('A student can access the reporting page.');
    }
    wp_set_current_user(0);
    if ($page->canAccess()) {
        throw new RuntimeException('A visitor can access the reporting page.');
    }
    wp_set_current_user($administrator);
    if (! $page->canAccess()) {
        throw new RuntimeException('An administrator cannot access the reporting page.');
    }

    $before = array(
        $wpdb->get_results($wpdb->prepare('SELECT course_id, user_id, status, completed_at FROM %i WHERE course_id IN (%d, %d, %d) ORDER BY course_id, user_id', $enrollments_table, $course, $without_quiz, $pagination_course), ARRAY_A),
        $wpdb->get_results($wpdb->prepare('SELECT user_id, lesson_id FROM %i WHERE lesson_id IN (%d, %d, %d) ORDER BY user_id, lesson_id', $progress_table, $lesson_ids[0], $lesson_ids[1], $no_quiz_lesson_ids[0]), ARRAY_A),
        $wpdb->get_results($wpdb->prepare('SELECT user_id, quiz_id, passed FROM %i WHERE course_id = %d ORDER BY user_id', $attempts_table, $course), ARRAY_A),
    );
    $summary = $reporting->summary();
    $summary_by_course = array();
    foreach ($summary['courses'] as $item) {
        $summary_by_course[$item['course_id']] = $item;
    }
    if (! isset($summary_by_course[$course]) || 6 !== $summary_by_course[$course]['total'] || 4 !== $summary_by_course[$course]['active'] || 1 !== $summary_by_course[$course]['completed'] || 1 !== $summary_by_course[$course]['revoked'] || 20 !== $summary_by_course[$course]['approval_rate'] || 88 !== $summary_by_course[$course]['average_progress']) {
        throw new RuntimeException('The course reporting summary metrics are incorrect.');
    }

    $detail = $reporting->detail($course);
    if (! is_array($detail) || 6 !== $detail['total'] || ! $detail['certificate_enabled']) {
        throw new RuntimeException('The course reporting detail is unavailable.');
    }
    $students_by_user = array();
    foreach ($detail['students'] as $student) {
        $students_by_user[$student['user_id']] = $student;
    }
    if ('En curso' !== $students_by_user[$active_full]['status_label'] || 100 !== $students_by_user[$active_full]['progress_percentage'] || 'Disponible' !== $students_by_user[$active_full]['quiz_label'] || 'En curso' !== $students_by_user[$active_partial]['status_label'] || 50 !== $students_by_user[$active_partial]['progress_percentage'] || 'No presentado' !== $students_by_user[$active_partial]['quiz_label'] || 'Aprobado' !== $students_by_user[$completed]['status_label'] || 'Aprobado' !== $students_by_user[$completed]['quiz_label'] || 1 !== $students_by_user[$completed]['attempts_used'] || 3 !== $students_by_user[$completed]['max_attempts'] || $now !== $students_by_user[$completed]['completed_at'] || 'Revocado' !== $students_by_user[$revoked]['status_label'] || 'Reprobado' !== $students_by_user[$failed]['quiz_label'] || 1 !== $students_by_user[$failed]['attempts_used'] || 'Intentos agotados' !== $students_by_user[$exhausted]['quiz_label'] || 3 !== $students_by_user[$exhausted]['attempts_used']) {
        throw new RuntimeException('The student reporting states are incorrect.');
    }
    $_GET = array('course_id' => (string) $course);
    ob_start();
    $page->render();
    $detail_markup = (string) ob_get_clean();
    $_GET = array();
    $expected_completed_at = wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($now . ' UTC'));
    if (! str_contains($detail_markup, $expected_completed_at)) {
        throw new RuntimeException('The approval date did not use the WordPress site timezone and format.');
    }

    $no_quiz_detail = $reporting->detail($without_quiz);
    $no_quiz_filter = $reporting->detail($without_quiz, 1, '', 'all', 'passed');
    if (! is_array($no_quiz_detail) || 'No aplica' !== $no_quiz_detail['students'][0]['quiz_label'] || null !== $no_quiz_detail['students'][0]['attempts_used'] || ! is_array($no_quiz_filter) || 0 !== $no_quiz_filter['total']) {
        throw new RuntimeException('A course without quiz did not render the expected report state.');
    }

    $unlimited_detail = $reporting->detail($unlimited_course);
    if (! is_array($unlimited_detail) || 2 !== $unlimited_detail['students'][0]['attempts_used'] || null !== $unlimited_detail['students'][0]['max_attempts'] || 'Reprobado' !== $unlimited_detail['students'][0]['quiz_label']) {
        throw new RuntimeException('An unlimited quiz did not render the expected report state.');
    }

    $search = $reporting->detail($course, 1, 'reporting-completed-' . $suffix . '@example.test');
    $completed_filter = $reporting->detail($course, 1, '', 'completed');
    $passed_filter = $reporting->detail($course, 1, '', 'all', 'passed');
    $not_passed_filter = $reporting->detail($course, 1, '', 'all', 'not_passed');
    $invalid_search = $reporting->detail($course, 1, "' OR 1=1 --");
    $invalid_filters = $reporting->detail($course, 1, '', 'invalid-status', 'invalid-quiz');
    if (! is_array($search) || 1 !== $search['total'] || $completed !== $search['students'][0]['user_id'] || ! is_array($completed_filter) || 1 !== $completed_filter['total'] || ! is_array($passed_filter) || 1 !== $passed_filter['total'] || $completed !== $passed_filter['students'][0]['user_id'] || ! is_array($not_passed_filter) || 5 !== $not_passed_filter['total'] || ! is_array($invalid_search) || 0 !== $invalid_search['total'] || ! is_array($invalid_filters) || 6 !== $invalid_filters['total'] || 'all' !== $invalid_filters['filters']['status'] || 'all' !== $invalid_filters['filters']['quiz']) {
        throw new RuntimeException('Reporting search or filters are incorrect or unsafe.');
    }

    $queries_before_page = $wpdb->num_queries;
    $first_page = $reporting->detail($pagination_course, 1);
    $page_query_count = $wpdb->num_queries - $queries_before_page;
    $second_page = $reporting->detail($pagination_course, 2);
    $out_of_range = $reporting->detail($pagination_course, 99);
    if (! is_array($first_page) || ! is_array($second_page) || ! is_array($out_of_range) || 26 !== $first_page['total'] || 25 !== count($first_page['students']) || 2 !== $first_page['total_pages'] || 1 !== count($second_page['students']) || 2 !== $out_of_range['page'] || array_intersect(array_column($first_page['students'], 'user_id'), array_column($second_page['students'], 'user_id')) !== array()) {
        throw new RuntimeException('Reporting pagination is incorrect.');
    }
    if ($page_query_count > 16) {
        throw new RuntimeException('The paginated report made too many queries for one page.');
    }

    $plus_search = $reporting->detail($pagination_course, 1, 'a+b-page');
    if (! is_array($plus_search) || 26 !== $plus_search['total'] || 2 !== $plus_search['total_pages']) {
        throw new RuntimeException('The reporting search did not retain plus signs.');
    }
    $_GET = array('course_id' => (string) $pagination_course, 'search' => 'a+b-page');
    ob_start();
    $page->render();
    $pagination_markup = (string) ob_get_clean();
    $_GET = array();
    if (! str_contains($pagination_markup, 'search=a%2Bb-page')) {
        throw new RuntimeException('The pagination link did not encode the search filter.');
    }

    if (null !== $reporting->detail(999999999)) {
        throw new RuntimeException('An invalid course ID was not handled safely.');
    }

    $after = array(
        $wpdb->get_results($wpdb->prepare('SELECT course_id, user_id, status, completed_at FROM %i WHERE course_id IN (%d, %d, %d) ORDER BY course_id, user_id', $enrollments_table, $course, $without_quiz, $pagination_course), ARRAY_A),
        $wpdb->get_results($wpdb->prepare('SELECT user_id, lesson_id FROM %i WHERE lesson_id IN (%d, %d, %d) ORDER BY user_id, lesson_id', $progress_table, $lesson_ids[0], $lesson_ids[1], $no_quiz_lesson_ids[0]), ARRAY_A),
        $wpdb->get_results($wpdb->prepare('SELECT user_id, quiz_id, passed FROM %i WHERE course_id = %d ORDER BY user_id', $attempts_table, $course), ARRAY_A),
    );
    if ($before !== $after) {
        throw new RuntimeException('Opening a report changed academic state.');
    }

    echo "Course administration and reporting integration: PASS\n";
} finally {
    wp_set_current_user(0);

    if (isset($wpdb) && $wpdb instanceof wpdb) {
        foreach ($user_ids as $user_id) {
            $wpdb->delete($wpdb->prefix . 'kaanbal_lesson_progress', array('user_id' => $user_id));
            $wpdb->delete($wpdb->prefix . 'kaanbal_enrollments', array('user_id' => $user_id));
        }

        if (isset($quiz, $unlimited_quiz)) {
            foreach (array($quiz, $unlimited_quiz) as $quiz_id) {
                $wpdb->query(
                    $wpdb->prepare(
                        'DELETE qa FROM ' . $wpdb->prefix . 'kaanbal_question_answers qa INNER JOIN ' . $wpdb->prefix . 'kaanbal_questions q ON q.id = qa.question_id WHERE q.quiz_id = %d',
                        $quiz_id
                    )
                );
            }
        }

        foreach ($post_ids as $post_id) {
            $wpdb->delete($wpdb->prefix . 'kaanbal_quiz_attempts', array('course_id' => $post_id));
            $wpdb->delete($wpdb->prefix . 'kaanbal_questions', array('quiz_id' => $post_id));
        }
    }

    foreach ($post_ids as $post_id) {
        wp_delete_post($post_id, true);
    }

    if (array() !== $user_ids) {
        require_once ABSPATH . 'wp-admin/includes/user.php';

        foreach ($user_ids as $user_id) {
            wp_delete_user($user_id);
        }
    }

    update_option('timezone_string', $original_timezone_string);
    update_option('gmt_offset', $original_gmt_offset);
}
