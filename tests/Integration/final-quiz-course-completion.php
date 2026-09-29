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

    $suffix = substr(str_replace('-', '', wp_generate_uuid4()), 0, 12);
    $create_post = static function (string $type, string $title) use (&$post_ids, $suffix): int {
        $id = wp_insert_post(array('post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_name' => sanitize_title($title . '-' . $suffix)));
        $post_ids[] = (int) $id;
        return (int) $id;
    };
    $user = static function (string $label, string $role = 'subscriber') use (&$user_ids, $suffix): int {
        $id = wp_insert_user(array('user_login' => 'quiz-' . $label . '-' . $suffix, 'user_pass' => wp_generate_password(), 'user_email' => 'quiz-' . $label . '-' . $suffix . '@example.test', 'role' => $role));
        if (is_wp_error($id)) {
            throw new RuntimeException('The quiz fixture user could not be created.');
        }
        $user_ids[] = (int) $id;
        return (int) $id;
    };

    $course_without_quiz = $create_post('kaanbal_course', 'Completion course without quiz');
    $course_with_quiz = $create_post('kaanbal_course', 'Completion course with quiz');
    $module_without_quiz = $create_post('kaanbal_module', 'Completion module without quiz');
    $module_with_quiz = $create_post('kaanbal_module', 'Completion module with quiz');
    $lesson_without_quiz = $create_post('kaanbal_lesson', 'Completion lesson without quiz');
    $lesson_with_quiz = $create_post('kaanbal_lesson', 'Completion lesson with quiz');
    $quiz = $create_post('kaanbal_quiz', 'Completion final quiz');
    $malformed_quiz = $create_post('kaanbal_quiz', 'Malformed completion quiz');
    $foreign_quiz = $create_post('kaanbal_quiz', 'Foreign completion quiz');
    update_post_meta($module_without_quiz, '_kaanbal_course_id', $course_without_quiz);
    update_post_meta($module_with_quiz, '_kaanbal_course_id', $course_with_quiz);
    update_post_meta($lesson_without_quiz, '_kaanbal_module_id', $module_without_quiz);
    update_post_meta($lesson_with_quiz, '_kaanbal_module_id', $module_with_quiz);
    update_post_meta($course_with_quiz, '_kaanbal_requires_final_quiz', '1');
    update_post_meta($quiz, '_kaanbal_course_id', $course_with_quiz);
    update_post_meta($quiz, '_kaanbal_passing_score', 80);

    $administrator = $user('administrator', 'administrator');
    $student = $user('student');
    $other = $user('other');
    $limited = $user('limited');
    $unlimited = $user('unlimited');
    $endpoint_student = $user('endpoint');
    $forged_user = $user('forged');
    $score_forged_user = $user('score-forged');
    $score_precision_user = $user('score-precision');
    global $wpdb;
    $enrollments_table = $wpdb->prefix . 'kaanbal_enrollments';
    $attempts_table = $wpdb->prefix . 'kaanbal_quiz_attempts';
    $attempt_answers_table = $wpdb->prefix . 'kaanbal_quiz_attempt_answers';
    $now = current_time('mysql', true);
    foreach (array($student, $other, $limited, $unlimited, $endpoint_student, $forged_user, $score_forged_user, $score_precision_user) as $user_id) {
        $wpdb->insert($enrollments_table, array('user_id' => $user_id, 'course_id' => $course_with_quiz, 'status' => 'active', 'enrolled_at' => $now, 'created_at' => $now, 'updated_at' => $now));
    }
    $wpdb->insert($enrollments_table, array('user_id' => $student, 'course_id' => $course_without_quiz, 'status' => 'active', 'enrolled_at' => $now, 'created_at' => $now, 'updated_at' => $now));

    $curriculum = new Kaanbal\Courses\Application\CurriculumService(new Kaanbal\Courses\Infrastructure\CurriculumRepository());
    $progress_store = new Kaanbal\Progress\Infrastructure\LessonProgressRepository();
    $progress = new Kaanbal\Progress\Application\CourseProgressService($curriculum, $progress_store);
    $enrollments = new Kaanbal\Enrollment\Infrastructure\EnrollmentRepository();
    $quizzes = new Kaanbal\Quiz\Infrastructure\QuizRepository();
    $questions = new Kaanbal\Quiz\Infrastructure\QuestionRepository();
    $answers = new Kaanbal\Quiz\Infrastructure\AnswerRepository();
    $attempts = new Kaanbal\Quiz\Infrastructure\QuizAttemptRepository();
    $completion = new Kaanbal\Quiz\Application\CourseCompletionService($progress, $quizzes, $attempts, $enrollments);
    $eligibility = new Kaanbal\Quiz\Application\QuizEligibilityService(new Kaanbal\Access\Application\CourseAccessService($enrollments), $progress, $quizzes, $questions, $answers, $attempts);
    $submission = new Kaanbal\Quiz\Application\QuizSubmissionService($eligibility, $quizzes, $questions, $answers, $attempts, new Kaanbal\Quiz\Application\QuizScoreCalculator(), $completion);

    wp_set_current_user($administrator);
    $_POST = array(
        'kaanbal_quiz_meta_nonce' => wp_create_nonce('kaanbal_save_quiz_meta'),
        'kaanbal_quiz_course_id'  => (string) $course_with_quiz,
        'kaanbal_passing_score'   => '80',
        'kaanbal_max_attempts'    => '',
        'question_text'           => 'What is two plus two?',
        'answers'                 => array('Three', 'Four', '', ''),
        'correct_answer'          => '1',
        'publish'                 => 'Publish',
    );
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This fixture sends a valid nonce through WordPress's save-post hook.
    $_REQUEST = $_POST;
    wp_update_post(array('ID' => $quiz, 'post_status' => 'publish'));
    $_POST = array();
    $_REQUEST = array();

    $saved_questions = $questions->activeForQuiz($quiz);
    $question_id = $saved_questions[0]['id'] ?? 0;
    $answer_sets = $answers->forQuestions(array($question_id));
    if (1 !== count($saved_questions) || 2 !== count($answer_sets[$question_id] ?? array()) || 'What is two plus two?' !== ($saved_questions[0]['question_text'] ?? '') || ! ($answer_sets[$question_id][1]['is_correct'] ?? false)) {
        throw new RuntimeException('Publishing a quiz did not save its question and answers.');
    }
    $wrong_answer = $answer_sets[$question_id][0]['id'];
    $correct_answer = $answer_sets[$question_id][1]['id'];
    $_POST = array(
        'kaanbal_quiz_meta_nonce' => wp_create_nonce('kaanbal_save_quiz_meta'),
        'kaanbal_quiz_course_id'  => (string) $course_with_quiz,
        'kaanbal_passing_score'   => '80',
        'kaanbal_max_attempts'    => '3',
        'question_text'           => '',
        'answers'                 => array('', '', '', ''),
        'correct_answer'          => '0',
        'publish'                 => 'Update',
    );
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This fixture sends a valid nonce through WordPress's save-post hook.
    $_REQUEST = $_POST;
    wp_update_post(array('ID' => $quiz, 'post_status' => 'publish'));
    $_POST = array();
    $_REQUEST = array();
    if (1 !== count($questions->activeForQuiz($quiz)) || 3 !== $quizzes->maxAttempts($quiz)) {
        throw new RuntimeException('Updating quiz settings required an additional question.');
    }
    delete_post_meta($quiz, '_kaanbal_max_attempts');
    update_post_meta($malformed_quiz, '_kaanbal_course_id', $course_with_quiz);

    $precision_score = (new Kaanbal\Quiz\Application\QuizScoreCalculator())->calculate(2, 3, 67);
    $attempts->record($score_precision_user, $course_with_quiz, $quiz, null, $precision_score, array());
    if ('66.67' !== $wpdb->get_var($wpdb->prepare('SELECT score FROM %i WHERE user_id = %d AND quiz_id = %d', $attempts_table, $score_precision_user, $quiz))) {
        throw new RuntimeException('Quiz scores were not persisted with two decimal places.');
    }

    $access = new Kaanbal\Access\Application\CourseAccessService($enrollments);
    $progress_store->complete($student, $lesson_without_quiz);
    if (! $completion->evaluate($student, $course_without_quiz) || 'completed' !== $wpdb->get_var($wpdb->prepare('SELECT status FROM %i WHERE user_id = %d AND course_id = %d', $enrollments_table, $student, $course_without_quiz)) || ! $access->canAccessCourse($student, $course_without_quiz)) {
        throw new RuntimeException('A course without a quiz did not complete at 100 percent progress.');
    }
    $completed_without_quiz_at = (string) $wpdb->get_var($wpdb->prepare('SELECT completed_at FROM %i WHERE user_id = %d AND course_id = %d', $enrollments_table, $student, $course_without_quiz));
    if ($completion->evaluate($student, $course_without_quiz) || $completed_without_quiz_at !== (string) $wpdb->get_var($wpdb->prepare('SELECT completed_at FROM %i WHERE user_id = %d AND course_id = %d', $enrollments_table, $student, $course_without_quiz))) {
        throw new RuntimeException('Course completion overwrote the original completion timestamp.');
    }
    $course_without_quiz_post = get_post($course_without_quiz);
    if (! $course_without_quiz_post instanceof WP_Post) {
        throw new RuntimeException('The certificate fixture course could not be retrieved.');
    }
    $router = new Kaanbal\Access\Presentation\Frontend\FrontendRouter();
    $render_course = static function (array $context): string {
        Kaanbal\Access\Presentation\Frontend\TemplateContext::replace($context);
        ob_start();
        require dirname(__DIR__, 2) . '/templates/frontend/course.php';

        return (string) ob_get_clean();
    };
    update_post_meta($course_without_quiz, '_kaanbal_certificate_enabled', '1');
    $certificate_response = $router->resolve($course_without_quiz_post->post_name, null, $student);
    $certificate_markup = $render_course($certificate_response['context']);
    if (200 !== $certificate_response['status'] || ! str_contains($certificate_markup, 'Has completado y aprobado el curso.') || ! str_contains($certificate_markup, 'hacerte llegar tu certificado')) {
        throw new RuntimeException('The certificate follow-up message was not rendered for an approved course.');
    }
    update_post_meta($course_without_quiz, '_kaanbal_certificate_enabled', '0');
    $without_certificate_response = $router->resolve($course_without_quiz_post->post_name, null, $student);
    $without_certificate_markup = $render_course($without_certificate_response['context']);
    if (! str_contains($without_certificate_markup, 'Has completado y aprobado el curso.') || str_contains($without_certificate_markup, 'hacerte llegar tu certificado')) {
        throw new RuntimeException('The success message without certificate was not rendered correctly.');
    }

    $progress_store->complete($student, $lesson_with_quiz);
    if ($completion->evaluate($student, $course_with_quiz)) {
        throw new RuntimeException('A course requiring a quiz completed before the quiz was passed.');
    }
    $course_with_quiz_post = get_post($course_with_quiz);
    if (! $course_with_quiz_post instanceof WP_Post) {
        throw new RuntimeException('The assessment fixture course could not be retrieved.');
    }
    $assessment_response = $router->resolve($course_with_quiz_post->post_name, null, $student);
    $assessment_markup = $render_course($assessment_response['context']);
    if (200 !== $assessment_response['status'] || ! str_contains($assessment_markup, 'Evaluación final') || str_contains($assessment_markup, 'Quiz final') || ! str_contains($assessment_markup, 'Comenzar evaluación') || ! str_contains($assessment_markup, '<dialog') || ! str_contains($assessment_markup, 'data-kaanbal-assessment-open') || ! str_contains($assessment_markup, 'What is two plus two?')) {
        throw new RuntimeException('The final assessment launch experience was not rendered correctly.');
    }

    $malformed_question = $questions->create($malformed_quiz, 'Malformed question', 0);
    $answers->create($malformed_question, 'Only answer', true, 0);
    $foreign_question = $questions->create($foreign_quiz, 'Foreign question', 0);
    $answers->create($foreign_question, 'Foreign answer', true, 0);
    $foreign_answer = $answers->forQuestions(array($foreign_question))[$foreign_question][0]['id'];

    if (Kaanbal\Quiz\Application\QuizEligibilityResult::LessonsIncomplete !== $eligibility->check($other, $course_with_quiz, $quiz)) {
        throw new RuntimeException('The quiz was available before the student completed lessons.');
    }

    if (Kaanbal\Quiz\Application\QuizEligibilityResult::InvalidQuiz !== $eligibility->check($student, $course_with_quiz, $malformed_quiz)) {
        throw new RuntimeException('A malformed question was accepted for a quiz.');
    }

    if (Kaanbal\Quiz\Application\QuizSubmissionResult::Rejected !== $submission->submit($student, $course_with_quiz, $quiz, array($question_id => $foreign_answer))) {
        throw new RuntimeException('An answer from another question could be submitted.');
    }

    if (Kaanbal\Quiz\Application\QuizSubmissionResult::Passed !== $submission->submit($student, $course_with_quiz, $quiz, array($question_id => $correct_answer))) {
        throw new RuntimeException('A valid quiz submission did not pass.');
    }

    $completed = $wpdb->get_row($wpdb->prepare('SELECT status, completed_at FROM %i WHERE user_id = %d AND course_id = %d', $enrollments_table, $student, $course_with_quiz), ARRAY_A);
    if (! is_array($completed) || 'completed' !== $completed['status'] || '' === $completed['completed_at'] || Kaanbal\Quiz\Application\QuizSubmissionResult::Rejected !== $submission->submit($student, $course_with_quiz, $quiz, array($question_id => $correct_answer)) || 1 !== (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d AND quiz_id = %d', $attempts_table, $student, $quiz)) || 1 !== (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i aa INNER JOIN %i a ON aa.attempt_id = a.id WHERE a.user_id = %d AND a.quiz_id = %d AND aa.question_text_snapshot = %s AND aa.answer_text_snapshot = %s', $attempt_answers_table, $attempts_table, $student, $quiz, 'What is two plus two?', 'Four'))) {
        throw new RuntimeException('Quiz completion was not idempotent.');
    }

    if (Kaanbal\Quiz\Application\QuizSubmissionResult::Rejected !== $submission->submit($student, $course_without_quiz, $quiz, array($question_id => $correct_answer))) {
        throw new RuntimeException('A quiz from another course could be submitted.');
    }

    foreach (array($limited, $unlimited, $endpoint_student, $forged_user, $score_forged_user) as $user_id) {
        $progress_store->complete($user_id, $lesson_with_quiz);
    }
    update_post_meta($quiz, '_kaanbal_max_attempts', 1);
    if (Kaanbal\Quiz\Application\QuizSubmissionResult::Failed !== $submission->submit($limited, $course_with_quiz, $quiz, array($question_id => $wrong_answer)) || Kaanbal\Quiz\Application\QuizSubmissionResult::Rejected !== $submission->submit($limited, $course_with_quiz, $quiz, array($question_id => $wrong_answer)) || 1 !== (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d AND quiz_id = %d', $attempts_table, $limited, $quiz))) {
        throw new RuntimeException('The maximum number of quiz attempts was not enforced.');
    }

    delete_post_meta($quiz, '_kaanbal_max_attempts');
    $progress_before_quiz = $progress->forCourse($unlimited, $course_with_quiz)->percentage;
    if (Kaanbal\Quiz\Application\QuizSubmissionResult::Failed !== $submission->submit($unlimited, $course_with_quiz, $quiz, array($question_id => $wrong_answer)) || Kaanbal\Quiz\Application\QuizSubmissionResult::Failed !== $submission->submit($unlimited, $course_with_quiz, $quiz, array($question_id => $wrong_answer)) || Kaanbal\Quiz\Application\QuizSubmissionResult::Passed !== $submission->submit($unlimited, $course_with_quiz, $quiz, array($question_id => $correct_answer)) || 3 !== (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d AND quiz_id = %d', $attempts_table, $unlimited, $quiz)) || $progress_before_quiz !== $progress->forCourse($unlimited, $course_with_quiz)->percentage) {
        throw new RuntimeException('Unlimited quiz attempts were not recorded independently.');
    }

    $endpoint = dirname(__DIR__) . '/Integration/support/quiz-endpoint-request.php';
    $run_endpoint = static function (int $user_id, int $course_id, int $quiz_id, int $question_id, int $answer_id, string $nonce, ?int $posted_user_id = null, ?int $score = null, ?int $passed = null) use ($endpoint, $wordpress_path): int {
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg($endpoint)
            . ' --user=' . escapeshellarg((string) $user_id)
            . ' --course=' . escapeshellarg((string) $course_id)
            . ' --quiz=' . escapeshellarg((string) $quiz_id)
            . ' --answers=' . escapeshellarg($question_id . ':' . $answer_id)
            . ' --nonce=' . escapeshellarg($nonce);
        if (null !== $posted_user_id) {
            $command .= ' --posted-user=' . escapeshellarg((string) $posted_user_id);
        }
        if (null !== $score) {
            $command .= ' --score=' . escapeshellarg((string) $score);
        }
        if (null !== $passed) {
            $command .= ' --passed=' . escapeshellarg((string) $passed);
        }
        $environment = array_merge(getenv(), array('KAANBAL_WP_PATH' => $wordpress_path));
        $process = proc_open($command, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, null, $environment);
        if (! is_resource($process)) {
            throw new RuntimeException('The quiz endpoint test process could not be started.');
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        return proc_close($process);
    };

    if (0 !== $run_endpoint($endpoint_student, $course_with_quiz, $quiz, $question_id, $correct_answer, 'valid') || 0 !== $run_endpoint($forged_user, $course_with_quiz, $quiz, $question_id, $correct_answer, 'valid', $endpoint_student) || 0 !== $run_endpoint($score_forged_user, $course_with_quiz, $quiz, $question_id, $wrong_answer, 'valid', null, 100, 1)) {
        throw new RuntimeException('The quiz endpoint did not accept an authorized submission.');
    }
    if (1 !== (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d AND quiz_id = %d', $attempts_table, $endpoint_student, $quiz)) || 1 !== (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d AND quiz_id = %d', $attempts_table, $forged_user, $quiz)) || 0 !== (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d AND quiz_id = %d AND passed = 1', $attempts_table, $score_forged_user, $quiz))) {
        throw new RuntimeException('The endpoint trusted a forged identity or client-side score.');
    }
    foreach (
        array(
        array(0, $course_with_quiz, $quiz, $question_id, $correct_answer, 'valid'),
        array($score_forged_user, $course_with_quiz, $quiz, $question_id, $wrong_answer, 'missing'),
        array($score_forged_user, $course_with_quiz, $quiz, $question_id, $wrong_answer, 'invalid'),
        array($score_forged_user, $course_without_quiz, $quiz, $question_id, $correct_answer, 'valid'),
        ) as $request
    ) {
        if (3 !== $run_endpoint(...$request)) {
            throw new RuntimeException('The quiz endpoint did not reject an unauthorized request.');
        }
    }

    echo "Final quiz and course completion integration: PASS\n";
} finally {
    if (isset($wpdb) && $wpdb instanceof wpdb) {
        foreach ($user_ids as $user_id) {
            $wpdb->delete($wpdb->prefix . 'kaanbal_lesson_progress', array('user_id' => $user_id));
            $wpdb->delete($wpdb->prefix . 'kaanbal_enrollments', array('user_id' => $user_id));
        }
        if (isset($quiz, $malformed_quiz, $foreign_quiz)) {
            foreach (array($quiz, $malformed_quiz, $foreign_quiz) as $quiz_id) {
                $wpdb->query($wpdb->prepare('DELETE aa FROM ' . $wpdb->prefix . 'kaanbal_quiz_attempt_answers aa INNER JOIN ' . $wpdb->prefix . 'kaanbal_quiz_attempts a ON aa.attempt_id = a.id WHERE a.quiz_id = %d', $quiz_id));
                $wpdb->delete($wpdb->prefix . 'kaanbal_quiz_attempts', array('quiz_id' => $quiz_id));
                $wpdb->query($wpdb->prepare('DELETE qa FROM ' . $wpdb->prefix . 'kaanbal_question_answers qa INNER JOIN ' . $wpdb->prefix . 'kaanbal_questions q ON qa.question_id = q.id WHERE q.quiz_id = %d', $quiz_id));
                $wpdb->delete($wpdb->prefix . 'kaanbal_questions', array('quiz_id' => $quiz_id));
            }
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
}
