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

final class ProgressModule implements BootableService
{
    public function register(): void
    {
        $action = new CompleteLessonAction(new CompleteLessonService(
            new CourseAccessService(new EnrollmentRepository()),
            new CurriculumService(new CurriculumRepository()),
            new LessonProgressRepository(),
        ));

        add_action('admin_post_' . CompleteLessonAction::ACTION, array($action, 'handle'));
        add_action('admin_post_nopriv_' . CompleteLessonAction::ACTION, array($action, 'handle'));
    }
}
