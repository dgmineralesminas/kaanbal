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
            wp_die(esc_html__('No puedes completar esta lección.', 'kaanbal'), '', array('response' => 403));
        }

        check_admin_referer('kaanbal_complete_lesson_' . $lesson_id, '_kaanbal_nonce');

        try {
            $result = $this->service->complete(get_current_user_id(), $course_id, $lesson_id);
        } catch (\RuntimeException) {
            wp_die(esc_html__('No fue posible guardar tu progreso. Inténtalo de nuevo.', 'kaanbal'), '', array('response' => 500));
        }

        if (! in_array($result, array(CompleteLessonResult::Completed, CompleteLessonResult::AlreadyCompleted), true)) {
            wp_die(esc_html__('No puedes completar esta lección.', 'kaanbal'), '', array('response' => 403));
        }

        $redirect = wp_get_referer();

        if (! is_string($redirect) || '' === $redirect) {
            $redirect = home_url('/');
        }

        wp_safe_redirect($redirect);
        exit;
    }
}
