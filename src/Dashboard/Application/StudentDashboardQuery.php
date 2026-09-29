<?php

declare(strict_types=1);

namespace Kaanbal\Dashboard\Application;

use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Progress\Application\CourseProgressService;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class StudentDashboardQuery
{
    public function __construct(
        private readonly EnrollmentRepository $enrollments,
        private readonly CourseProgressService $progress,
        private readonly QuizRepository $quizzes,
        private readonly QuizAttemptRepository $attempts,
    ) {
    }

    /** @return array{courses: list<array<string, mixed>>} */
    public function forUser(int $user_id): array
    {
        if ($user_id <= 0) {
            return array('courses' => array());
        }

        $enrollments = $this->enrollments->forUser($user_id);
        $course_ids = array_values(array_unique(array_column($enrollments, 'course_id')));

        if (array() === $course_ids) {
            return array('courses' => array());
        }

        $courses = get_posts(
            array(
                'post_type'      => 'kaanbal_course',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'post__in'       => $course_ids,
                'orderby'        => 'post__in',
            )
        );
        $courses_by_id = array();

        foreach ($courses as $course) {
            $courses_by_id[$course->ID] = $course;
        }

        $course_ids = array_values(array_intersect($course_ids, array_keys($courses_by_id)));

        if (array() === $course_ids) {
            return array('courses' => array());
        }

        update_meta_cache('post', $course_ids);
        $progress = $this->progress->forCourses($user_id, $course_ids);
        $quiz_details = $this->quizzes->dashboardDetailsForCourses($course_ids);
        $quiz_ids = array_values(array_filter(array_column($quiz_details, 'quiz_id')));
        $attempt_summaries = $this->attempts->summariesForUserAndQuizzes($user_id, $quiz_ids);
        $items = array();

        foreach ($enrollments as $enrollment) {
            $course_id = $enrollment['course_id'];
            $course = $courses_by_id[$course_id] ?? null;

            if (! $course instanceof \WP_Post) {
                continue;
            }

            $course_progress = $progress[$course_id] ?? new \Kaanbal\Progress\Application\CourseProgress(0, 0, 0, array());
            $quiz = $quiz_details[$course_id] ?? array('required' => false, 'certificate_enabled' => false, 'quiz_id' => null, 'max_attempts' => null);
            $quiz_id = $quiz['quiz_id'];
            $attempts = is_int($quiz_id) ? ($attempt_summaries[$quiz_id] ?? array('attempts_used' => 0, 'passed' => false)) : array('attempts_used' => 0, 'passed' => false);
            $state = QuizDashboardState::fromDashboardData($quiz['required'], $quiz_id, $course_progress->percentage, $attempts['passed'], $attempts['attempts_used'], $quiz['max_attempts']);
            $remaining = null === $quiz['max_attempts'] ? null : max(0, $quiz['max_attempts'] - $attempts['attempts_used']);

            $items[] = array(
                'course_id'           => $course_id,
                'title'               => $course->post_title,
                'image_url'           => get_the_post_thumbnail_url($course, 'large') ?: null,
                'instructor'          => (string) get_post_meta($course_id, '_kaanbal_instructor_name', true),
                'duration'            => (string) get_post_meta($course_id, '_kaanbal_duration', true),
                'enrollment_status'   => $enrollment['status'],
                'status_label'        => 'completed' === $enrollment['status'] ? __('Aprobado', 'kaanbal') : __('En curso', 'kaanbal'),
                'completed_at'        => $enrollment['completed_at'],
                'progress'            => $course_progress,
                'quiz'                => array(
                    'required'           => $quiz['required'],
                    'state'              => $state->value,
                    'attempts_used'      => $attempts['attempts_used'],
                    'attempts_remaining' => $remaining,
                    'attempts_limited'   => null !== $quiz['max_attempts'],
                ),
                'certificate_enabled' => $quiz['certificate_enabled'],
                'access_url'          => home_url('/courses/' . rawurlencode($course->post_name) . '/'),
                'action_label'        => 'completed' === $enrollment['status'] ? __('Ver curso', 'kaanbal') : __('Continuar curso', 'kaanbal'),
            );
        }

        return array('courses' => $items);
    }
}
