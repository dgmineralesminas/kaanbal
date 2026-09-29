<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Presentation\Admin;

use Kaanbal\Courses\Infrastructure\ContentTypes;
use Kaanbal\Quiz\Infrastructure\AnswerRepository;
use Kaanbal\Quiz\Infrastructure\QuestionRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class QuizMetaBoxes
{
    private const COURSE_NONCE = 'kaanbal_quiz_course_nonce';
    private const QUIZ_NONCE = 'kaanbal_quiz_meta_nonce';

    public function register(): void
    {
        add_meta_box('kaanbal-course-completion', __('Final quiz and certificate', 'kaanbal'), array($this, 'renderCourse'), ContentTypes::COURSE, 'normal', 'default');
        add_meta_box('kaanbal-quiz-settings', __('Quiz settings', 'kaanbal'), array($this, 'renderQuiz'), ContentTypes::QUIZ, 'normal', 'default');
        add_meta_box('kaanbal-quiz-questions', __('Questions', 'kaanbal'), array($this, 'renderQuestions'), ContentTypes::QUIZ, 'normal', 'default');
    }

    public function renderCourse(\WP_Post $post): void
    {
        wp_nonce_field('kaanbal_save_quiz_course', self::COURSE_NONCE);
        $requires = '1' === (string) get_post_meta($post->ID, QuizRepository::REQUIRES_QUIZ_META, true);
        $certificate = '1' === (string) get_post_meta($post->ID, QuizRepository::CERTIFICATE_META, true);
        echo '<p><label><input type="checkbox" name="kaanbal_requires_final_quiz" value="1"' . checked($requires, true, false) . ' /> ' . esc_html__('This course requires a final quiz.', 'kaanbal') . '</label></p>';
        echo '<p><label><input type="checkbox" name="kaanbal_certificate_enabled" value="1"' . checked($certificate, true, false) . ' /> ' . esc_html__('Certificate follow-up is provided outside Kaanbal.', 'kaanbal') . '</label></p>';
    }

    public function renderQuiz(\WP_Post $post): void
    {
        wp_nonce_field('kaanbal_save_quiz_meta', self::QUIZ_NONCE);
        $course_id = (int) get_post_meta($post->ID, QuizRepository::COURSE_ID_META, true);
        $passing = (int) get_post_meta($post->ID, QuizRepository::PASSING_SCORE_META, true) ?: 80;
        $max_attempts = get_post_meta($post->ID, QuizRepository::MAX_ATTEMPTS_META, true);
        $courses = get_posts(array('post_type' => ContentTypes::COURSE, 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
        echo '<p><label for="kaanbal-quiz-course">' . esc_html__('Course', 'kaanbal') . '</label><br /><select class="widefat" id="kaanbal-quiz-course" name="kaanbal_quiz_course_id"><option value="">' . esc_html__('Select a course', 'kaanbal') . '</option>';
        foreach ($courses as $course) {
            echo '<option value="' . esc_attr((string) $course->ID) . '"' . selected($course_id, $course->ID, false) . '>' . esc_html(get_the_title($course)) . '</option>';
        }
        echo '</select></p>';
        echo '<p><label for="kaanbal-passing-score">' . esc_html__('Passing score (%)', 'kaanbal') . '</label><br /><input id="kaanbal-passing-score" name="kaanbal_passing_score" type="number" min="1" max="100" value="' . esc_attr((string) $passing) . '" /></p>';
        echo '<p><label for="kaanbal-max-attempts">' . esc_html__('Maximum attempts', 'kaanbal') . '</label><br /><input id="kaanbal-max-attempts" name="kaanbal_max_attempts" type="number" min="1" value="' . esc_attr((string) $max_attempts) . '" /><br /><small>' . esc_html__('Leave blank for unlimited attempts.', 'kaanbal') . '</small></p>';
    }

    public function renderQuestions(\WP_Post $post): void
    {
        $questions = new QuestionRepository();
        $answers = new AnswerRepository();
        $items = $questions->activeForQuiz($post->ID);
        $answer_sets = $answers->forQuestions(array_column($items, 'id'));
        echo '<ul>';
        foreach ($items as $question) {
            echo '<li><strong>' . esc_html($question['question_text']) . '</strong><ul>';
            foreach ($answer_sets[$question['id']] ?? array() as $answer) {
                echo '<li>' . esc_html($answer['answer_text']) . ($answer['is_correct'] ? ' ✓' : '') . '</li>';
            }
            echo '</ul></li>';
        }
        echo '</ul>';
        echo '<p>' . esc_html__('Complete every question field and use Add question to add it. You can publish or update only the quiz settings without adding a question.', 'kaanbal') . '</p>';
        echo '<p><label>' . esc_html__('Question', 'kaanbal') . '<br /><textarea class="widefat" name="question_text"></textarea></label></p>';
        for ($index = 0; $index < 4; $index++) {
            echo '<p><label><input type="radio" name="correct_answer" value="' . esc_attr((string) $index) . '"' . checked(0, $index, false) . ' /> ' . esc_html(sprintf(__('Answer %d', 'kaanbal'), $index + 1)) . '<br /><input class="widefat" type="text" name="answers[]" /></label></p>';
        }
        submit_button(__('Add question', 'kaanbal'), 'secondary', 'kaanbal_add_quiz_question', false);
    }

    public function saveCourse(int $post_id): void
    {
        if (! $this->canSave($post_id, self::COURSE_NONCE, 'kaanbal_save_quiz_course')) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- canSave verified the nonce.
        update_post_meta($post_id, QuizRepository::REQUIRES_QUIZ_META, isset($_POST['kaanbal_requires_final_quiz']) ? '1' : '0');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- canSave verified the nonce.
        update_post_meta($post_id, QuizRepository::CERTIFICATE_META, isset($_POST['kaanbal_certificate_enabled']) ? '1' : '0');
    }

    public function saveQuiz(int $post_id): void
    {
        if (! $this->canSave($post_id, self::QUIZ_NONCE, 'kaanbal_save_quiz_meta')) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- canSave verified the nonce.
        $course_id = absint(wp_unslash($_POST['kaanbal_quiz_course_id'] ?? ''));
        if ($course_id > 0 && ContentTypes::COURSE === get_post_type($course_id)) {
            $quizzes = new QuizRepository();
            if ($quizzes->hasAnotherPublishedQuizForCourse($course_id, $post_id)) {
                delete_post_meta($post_id, QuizRepository::COURSE_ID_META);
            } else {
                update_post_meta($post_id, QuizRepository::COURSE_ID_META, $course_id);
            }
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- canSave verified the nonce.
        update_post_meta($post_id, QuizRepository::PASSING_SCORE_META, max(1, min(100, absint(wp_unslash($_POST['kaanbal_passing_score'] ?? 80)))));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- canSave verified the nonce.
        $max_attempts = absint(wp_unslash($_POST['kaanbal_max_attempts'] ?? ''));
        if ($max_attempts > 0) {
            update_post_meta($post_id, QuizRepository::MAX_ATTEMPTS_META, $max_attempts);
        } else {
            delete_post_meta($post_id, QuizRepository::MAX_ATTEMPTS_META);
        }

        $this->saveQuestion($post_id);
    }

    private function saveQuestion(int $quiz_id): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- saveQuiz verified the quiz edit nonce.
        $question_text = sanitize_textarea_field(wp_unslash($_POST['question_text'] ?? ''));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- saveQuiz verified the quiz edit nonce.
        $submitted_answers = isset($_POST['answers']) && is_array($_POST['answers']) ? array_map(static fn (mixed $answer): string => sanitize_text_field(wp_unslash((string) $answer)), $_POST['answers']) : array();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- saveQuiz verified the quiz edit nonce.
        $correct = absint(wp_unslash($_POST['correct_answer'] ?? ''));
        $submitted_answers = array_filter($submitted_answers, static fn (string $answer): bool => '' !== $answer);

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- saveQuiz verified the quiz edit nonce.
        $adding_question = isset($_POST['kaanbal_add_quiz_question']);

        if ('' === $question_text && array() === $submitted_answers) {
            return;
        }

        if ('' === $question_text || count($submitted_answers) < 2 || ! array_key_exists($correct, $submitted_answers)) {
            if ($adding_question) {
                add_filter('redirect_post_location', static fn (string $location): string => add_query_arg('kaanbal_quiz_error', 'question', $location));
            }
            return;
        }

        $questions = new QuestionRepository();
        $answers = new AnswerRepository();
        $question_id = $questions->create($quiz_id, $question_text, count($questions->activeForQuiz($quiz_id)));

        foreach ($submitted_answers as $position => $answer) {
            $answers->create($question_id, $answer, $position === $correct, $position);
        }
    }

    private function canSave(int $post_id, string $nonce_name, string $action): bool
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This method is the shared nonce verification boundary.
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || ! isset($_POST[$nonce_name])) {
            return false;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This method verifies the request nonce before reads proceed.
        return wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_name])), $action) && current_user_can('edit_post', $post_id);
    }
}
