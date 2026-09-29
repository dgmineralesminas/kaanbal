<?php

declare(strict_types=1);

namespace Kaanbal\Progress;

use Kaanbal\Access\Application\CourseAccessService;
use Kaanbal\Bootstrap\BootableService;
use Kaanbal\Courses\Application\CurriculumService;
use Kaanbal\Courses\Infrastructure\CurriculumRepository;
use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Progress\Application\CompleteLessonService;
use Kaanbal\Progress\Infrastructure\LessonProgressRepository;
use Kaanbal\Progress\Presentation\Frontend\CompleteLessonAction;
use Kaanbal\Quiz\Application\CourseCompletionService;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class ProgressModule implements BootableService
{
    public function register(): void
    {
        $curriculum = new CurriculumService(new CurriculumRepository());
        $progress = new LessonProgressRepository();
        $enrollments = new EnrollmentRepository();
        $completion = new CourseCompletionService(
            new \Kaanbal\Progress\Application\CourseProgressService($curriculum, $progress),
            new QuizRepository(),
            new QuizAttemptRepository(),
            $enrollments,
        );
        $action = new CompleteLessonAction(new CompleteLessonService(new CourseAccessService($enrollments), $curriculum, $progress, $completion));

        add_action('admin_post_' . CompleteLessonAction::ACTION, array($action, 'handle'));
        add_action('admin_post_nopriv_' . CompleteLessonAction::ACTION, array($action, 'handle'));
    }
}
