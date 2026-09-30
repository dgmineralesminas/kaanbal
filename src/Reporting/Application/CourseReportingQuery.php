<?php

declare(strict_types=1);

namespace Kaanbal\Reporting\Application;

use Kaanbal\Courses\Infrastructure\ContentTypes;
use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Progress\Application\CourseProgress;
use Kaanbal\Progress\Application\CourseProgressService;
use Kaanbal\Quiz\Application\QuizValidityService;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class CourseReportingQuery
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly EnrollmentRepository $enrollments,
        private readonly CourseProgressService $progress,
        private readonly QuizRepository $quizzes,
        private readonly QuizAttemptRepository $attempts,
        private readonly QuizValidityService $validity,
        private readonly CourseReportMetrics $metrics = new CourseReportMetrics(),
    ) {
    }

    /** @return array{courses: list<array<string, int|string|bool>>} */
    public function summary(): array
    {
        $query = new \WP_Query(
            array(
                'post_type'           => ContentTypes::COURSE,
                'post_status'         => 'publish',
                'posts_per_page'      => -1,
                'orderby'             => array('title' => 'ASC', 'ID' => 'ASC'),
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
            )
        );
        $courses = array_values(array_filter($query->posts, static fn (mixed $course): bool => $course instanceof \WP_Post));
        $course_ids = array_map(static fn (\WP_Post $course): int => $course->ID, $courses);
        $summaries = $this->enrollments->summariesForCourses($course_ids);
        $active_users = $this->enrollments->activeUserIdsForCourses($course_ids);
        $progress = $this->progress->forUsersOnCourses($active_users);
        $items = array();

        foreach ($courses as $course) {
            $course_id = $course->ID;
            $counts = $summaries[$course_id] ?? array('total' => 0, 'active' => 0, 'completed' => 0, 'revoked' => 0);
            $percentages = array_map(
                static fn (CourseProgress $course_progress): int => $course_progress->percentage,
                array_values($progress[$course_id] ?? array())
            );
            $items[] = array(
                'course_id'        => $course_id,
                'title'            => $course->post_title,
                'total'            => $counts['total'],
                'active'           => $counts['active'],
                'completed'        => $counts['completed'],
                'revoked'          => $counts['revoked'],
                'approval_rate'    => $this->metrics->approvalRate($counts['active'], $counts['completed']),
                'average_progress' => $this->metrics->averageProgress($percentages),
            );
        }

        return array('courses' => $items);
    }

    /**
     * @return array{course: \WP_Post, students: list<array<string, int|string|bool|null>>, total: int, page: int, total_pages: int, filters: array{search: string, status: string, quiz: string}, certificate_enabled: bool}|null
     */
    public function detail(int $course_id, int $page = 1, string $search = '', string $status = 'all', string $quiz_filter = 'all'): ?array
    {
        $course = get_post($course_id);

        if (! $course instanceof \WP_Post || ContentTypes::COURSE !== $course->post_type || 'publish' !== $course->post_status) {
            return null;
        }

        $status = in_array($status, array('all', 'active', 'completed', 'revoked'), true) ? $status : 'all';
        $quiz_filter = in_array($quiz_filter, array('all', 'passed', 'not_passed'), true) ? $quiz_filter : 'all';
        $search = trim($search);
        $quiz = $this->quizzes->dashboardDetailsForCourses(array($course->ID))[$course->ID] ?? array(
            'required'            => false,
            'certificate_enabled' => false,
            'quiz_id'             => null,
            'max_attempts'        => null,
        );
        $quiz_id = $quiz['quiz_id'];
        $valid_quizzes = is_int($quiz_id) ? array_fill_keys($this->validity->validQuizIds(array($quiz_id)), true) : array();
        $has_filterable_quiz = $quiz['required'] && is_int($quiz_id);
        $page_data = 'all' !== $quiz_filter && ! $has_filterable_quiz
            ? array('items' => array(), 'total' => 0, 'page' => 1, 'total_pages' => 1)
            : $this->enrollments->studentsForCourse(
                $course->ID,
                $page,
                self::PER_PAGE,
                $status,
                $search,
                $quiz_filter,
                $has_filterable_quiz ? $quiz_id : null,
            );
        $user_ids = array_column($page_data['items'], 'user_id');
        $progress = $this->progress->forUsersOnCourses(array($course->ID => $user_ids));
        $attempts = is_int($quiz_id)
            ? $this->attempts->summariesForUsersAndQuizzes($user_ids, array($quiz_id))
            : array();
        $students = array();

        foreach ($page_data['items'] as $item) {
            $user_id = $item['user_id'];
            $course_progress = $progress[$course->ID][$user_id] ?? new CourseProgress(0, 0, 0, array());
            $attempt = is_int($quiz_id) ? ($attempts[$user_id][$quiz_id] ?? array('attempts_used' => 0, 'passed' => false)) : array('attempts_used' => 0, 'passed' => false);
            $quiz_state = \Kaanbal\Dashboard\Application\QuizDashboardState::fromDashboardData(
                $quiz['required'],
                $quiz_id,
                is_int($quiz_id) && isset($valid_quizzes[$quiz_id]),
                $course_progress->percentage,
                $attempt['passed'],
                $attempt['attempts_used'],
                $quiz['max_attempts'],
            );
            $students[] = array(
                'user_id'            => $user_id,
                'name'               => $item['display_name'] ?? __('Usuario eliminado', 'kaanbal'),
                'email'              => $item['email'],
                'enrollment_status'  => $item['status'],
                'status_label'       => $this->statusLabel($item['status']),
                'completed_at'       => $item['completed_at'],
                'completed_lessons'  => $course_progress->completed_lessons,
                'total_lessons'      => $course_progress->total_lessons,
                'progress_percentage' => $course_progress->percentage,
                'quiz_state'         => $quiz_state->value,
                'quiz_label'         => $this->quizLabel($quiz_state),
                'attempts_used'      => $quiz['required'] ? $attempt['attempts_used'] : null,
                'max_attempts'       => $quiz['required'] ? $quiz['max_attempts'] : null,
            );
        }

        return array(
            'course'              => $course,
            'students'            => $students,
            'total'               => $page_data['total'],
            'page'                => $page_data['page'],
            'total_pages'         => $page_data['total_pages'],
            'filters'             => array('search' => $search, 'status' => $status, 'quiz' => $quiz_filter),
            'certificate_enabled' => $quiz['certificate_enabled'],
        );
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'completed' => __('Aprobado', 'kaanbal'),
            'revoked' => __('Revocado', 'kaanbal'),
            default => __('En curso', 'kaanbal'),
        };
    }

    private function quizLabel(\Kaanbal\Dashboard\Application\QuizDashboardState $state): string
    {
        return match ($state) {
            \Kaanbal\Dashboard\Application\QuizDashboardState::NotRequired => __('No aplica', 'kaanbal'),
            \Kaanbal\Dashboard\Application\QuizDashboardState::Locked => __('No presentado', 'kaanbal'),
            \Kaanbal\Dashboard\Application\QuizDashboardState::Available => __('Disponible', 'kaanbal'),
            \Kaanbal\Dashboard\Application\QuizDashboardState::FailedCanRetry => __('Reprobado', 'kaanbal'),
            \Kaanbal\Dashboard\Application\QuizDashboardState::Passed => __('Aprobado', 'kaanbal'),
            \Kaanbal\Dashboard\Application\QuizDashboardState::NoAttemptsLeft => __('Intentos agotados', 'kaanbal'),
            \Kaanbal\Dashboard\Application\QuizDashboardState::Unavailable => __('No disponible', 'kaanbal'),
        };
    }
}
