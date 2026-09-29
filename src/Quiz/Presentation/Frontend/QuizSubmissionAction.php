<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Presentation\Frontend;

use Kaanbal\Quiz\Application\QuizSubmissionService;

final class QuizSubmissionAction
{
    public const ACTION = 'kaanbal_submit_final_quiz';

    public function __construct(private readonly QuizSubmissionService $submission)
    {
    }

    public function handle(): void
    {
        $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
        $quiz_id = isset($_POST['quiz_id']) ? absint($_POST['quiz_id']) : 0;

        if ($course_id <= 0 || $quiz_id <= 0 || ! is_user_logged_in()) {
            wp_die(esc_html__('No puedes presentar este quiz.', 'kaanbal'), '', array('response' => 403));
        }

        check_admin_referer('kaanbal_submit_final_quiz_' . $quiz_id, '_kaanbal_nonce');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- check_admin_referer verified the nonce above.
        $answers = isset($_POST['answers']) && is_array($_POST['answers']) ? wp_unslash($_POST['answers']) : array();

        try {
            $result = $this->submission->submit(get_current_user_id(), $course_id, $quiz_id, $answers);
        } catch (\RuntimeException) {
            wp_die(esc_html__('No fue posible registrar tu intento. Inténtalo de nuevo.', 'kaanbal'), '', array('response' => 500));
        }

        if ('rejected' === $result->value) {
            wp_die(esc_html__('No puedes presentar este quiz.', 'kaanbal'), '', array('response' => 403));
        }

        $redirect = wp_get_referer();
        wp_safe_redirect(is_string($redirect) && '' !== $redirect ? $redirect : home_url('/'));
        exit;
    }
}
