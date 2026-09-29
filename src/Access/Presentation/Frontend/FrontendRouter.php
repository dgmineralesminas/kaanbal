<?php

declare(strict_types=1);

namespace Kaanbal\Access\Presentation\Frontend;

use Kaanbal\Access\Application\CourseAccessResult;
use Kaanbal\Access\Application\CourseAccessService;
use Kaanbal\Access\Application\LessonNavigationService;
use Kaanbal\Courses\Application\CurriculumService;
use Kaanbal\Courses\Infrastructure\ContentTypes;
use Kaanbal\Courses\Infrastructure\CurriculumRepository;
use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Progress\Application\CourseProgressService;
use Kaanbal\Progress\Infrastructure\LessonProgressRepository;
use Kaanbal\Quiz\Application\CourseQuizStatusService;
use Kaanbal\Quiz\Application\QuizEligibilityService;
use Kaanbal\Quiz\Application\QuizValidityService;
use Kaanbal\Quiz\Infrastructure\AnswerRepository;
use Kaanbal\Quiz\Infrastructure\QuestionRepository;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class FrontendRouter
{
    public const COURSE_QUERY_VAR = 'kaanbal_course';
    public const LESSON_QUERY_VAR = 'kaanbal_lesson';

    private readonly CurriculumService $curriculum;

    private readonly CourseAccessService $access;

    private readonly LessonNavigationService $navigation;

    private readonly CourseProgressService $progress;

    private readonly CourseQuizStatusService $quiz;

    private readonly string $templates_path;

    public function __construct(
        ?CurriculumService $curriculum = null,
        ?CourseAccessService $access = null,
        ?LessonNavigationService $navigation = null,
        ?string $templates_path = null,
        ?CourseProgressService $progress = null,
        ?CourseQuizStatusService $quiz = null,
    ) {
        $this->curriculum = $curriculum ?? new CurriculumService(new CurriculumRepository());
        $this->access     = $access ?? new CourseAccessService(new EnrollmentRepository());
        $this->navigation = $navigation ?? new LessonNavigationService();
        $this->progress = $progress ?? new CourseProgressService($this->curriculum, new LessonProgressRepository());
        $this->quiz = $quiz ?? $this->quizStatusService();
        $this->templates_path = $templates_path ?? dirname(__DIR__, 4) . '/templates/frontend/';
    }

    /** @param list<string> $query_vars
     * @return list<string>
     */
    public function queryVars(array $query_vars): array
    {
        $query_vars[] = self::COURSE_QUERY_VAR;
        $query_vars[] = self::LESSON_QUERY_VAR;

        return array_values(array_unique($query_vars));
    }

    public function template(string $template): string
    {
        $course_slug = get_query_var(self::COURSE_QUERY_VAR);

        if (! is_string($course_slug) || '' === $course_slug) {
            return $template;
        }

        $lesson_slug = get_query_var(self::LESSON_QUERY_VAR);
        $response    = $this->resolve(
            $course_slug,
            is_string($lesson_slug) && '' !== $lesson_slug ? $lesson_slug : null,
            get_current_user_id()
        );

        TemplateContext::replace($response['context']);

        if (404 === $response['status']) {
            global $wp_query;

            if ($wp_query instanceof \WP_Query) {
                $wp_query->set_404();
            }

            status_header(404);
            nocache_headers();
        }

        if (403 === $response['status']) {
            status_header(403);
            nocache_headers();
        }

        return $this->templates_path . $response['template'] . '.php';
    }

    /**
     * @return array{template: 'course'|'lesson'|'access-denied'|'not-found', status: 200|403|404, context: array<string, mixed>}
     */
    public function resolve(string $course_slug, ?string $lesson_slug, int $user_id): array
    {
        $course = get_page_by_path($course_slug, 'OBJECT', ContentTypes::COURSE);

        if (! $course instanceof \WP_Post) {
            return $this->notFound();
        }

        $curriculum = $this->curriculum->forPublishedCourse($course->ID);

        if (! is_array($curriculum)) {
            return $this->notFound();
        }

        $access = $this->access->check($user_id, $course->ID);

        if (! $access->isGranted()) {
            return $this->accessDenied($access);
        }

        if (null === $lesson_slug) {
            return array(
                'template' => 'course',
                'status'   => 200,
                'context'  => array(
                    'curriculum' => $curriculum,
                    'progress'   => $this->progress->forCourse($user_id, $course->ID),
                    'quiz'       => $this->quiz->forCourse($user_id, $course->ID),
                ),
            );
        }

        $lesson = get_page_by_path($lesson_slug, 'OBJECT', ContentTypes::LESSON);

        if (! $lesson instanceof \WP_Post) {
            return $this->notFound();
        }

        $hierarchy = $this->curriculum->hierarchyForPublishedLesson($lesson->ID);

        if (! is_array($hierarchy) || $course->ID !== $hierarchy['course']->ID) {
            return $this->notFound();
        }

        return array(
            'template' => 'lesson',
            'status'   => 200,
            'context'  => array(
                'course'     => $course,
                'curriculum' => $curriculum,
                'module'     => $hierarchy['module'],
                'lesson'     => $lesson,
                'navigation' => $this->navigation->forLesson($lesson->ID, $curriculum),
                'progress'   => $this->progress->forCourse($user_id, $course->ID),
                'quiz'       => $this->quiz->forCourse($user_id, $course->ID),
            ),
        );
    }

    /** @return array{template: 'access-denied', status: 403, context: array{reason: string}} */
    private function accessDenied(CourseAccessResult $result): array
    {
        return array(
            'template' => 'access-denied',
            'status'   => 403,
            'context'  => array('reason' => $result->value),
        );
    }

    /** @return array{template: 'not-found', status: 404, context: array{}} */
    private function notFound(): array
    {
        return array(
            'template' => 'not-found',
            'status'   => 404,
            'context'  => array(),
        );
    }

    private function quizStatusService(): CourseQuizStatusService
    {
        $quizzes = new QuizRepository();
        $questions = new QuestionRepository();
        $answers = new AnswerRepository();
        $attempts = new QuizAttemptRepository();

        $validity = new QuizValidityService($questions);

        return new CourseQuizStatusService(
            $quizzes,
            $questions,
            $answers,
            new QuizEligibilityService($this->access, $this->progress, $quizzes, $validity, $attempts),
            $validity,
        );
    }
}
