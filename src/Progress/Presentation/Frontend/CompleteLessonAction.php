<?php

declare(strict_types=1);

namespace Kaanbal\Progress\Presentation\Frontend;

use Kaanbal\Progress\Application\CompleteLessonResult;
use Kaanbal\Progress\Application\CompleteLessonService;

final class CompleteLessonAction
{
    public const ACTION = 'kaanbal_complete_lesson';

    public function __construct(private readonly CompleteLessonService $service)
    {
    }

    public function handle(): void
    {
        $lesson_id = isset($_POST['lesson_id']) ? absint($_POST['lesson_id']) : 0;
        $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;

        if ($lesson_id <= 0 || ! is_user_logged_in()) {
            wp_die(esc_html__('You cannot complete this lesson.', 'kaanbal'), '', array('response' => 403));
        }

        check_admin_referer('kaanbal_complete_lesson_' . $lesson_id, '_kaanbal_nonce');

        $result = $this->service->complete(get_current_user_id(), $course_id, $lesson_id);

        if (! in_array($result, array(CompleteLessonResult::Completed, CompleteLessonResult::AlreadyCompleted), true)) {
            wp_die(esc_html__('You cannot complete this lesson.', 'kaanbal'), '', array('response' => 403));
        }

        $redirect = wp_get_referer();

        if (! is_string($redirect) || '' === $redirect) {
            $redirect = home_url('/');
        }

        wp_safe_redirect(add_query_arg('kaanbal_progress', $result->value, $redirect));
        exit;
    }
}
