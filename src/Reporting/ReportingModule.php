<?php

declare(strict_types=1);

namespace Kaanbal\Reporting;

use Kaanbal\Bootstrap\BootableService;
use Kaanbal\Courses\Application\CurriculumService;
use Kaanbal\Courses\Infrastructure\CurriculumRepository;
use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Progress\Application\CourseProgressService;
use Kaanbal\Progress\Infrastructure\LessonProgressRepository;
use Kaanbal\Quiz\Application\QuizValidityService;
use Kaanbal\Quiz\Infrastructure\QuestionRepository;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;
use Kaanbal\Reporting\Application\CourseReportingQuery;
use Kaanbal\Reporting\Presentation\Admin\CourseReportsPage;

final class ReportingModule implements BootableService
{
    public function register(): void
    {
        add_action('admin_menu', array(new CourseReportsPage($this->query()), 'register'));
    }

    private function query(): CourseReportingQuery
    {
        $curriculum = new CurriculumService(new CurriculumRepository());

        return new CourseReportingQuery(
            new EnrollmentRepository(),
            new CourseProgressService($curriculum, new LessonProgressRepository()),
            new QuizRepository(),
            new QuizAttemptRepository(),
            new QuizValidityService(new QuestionRepository()),
        );
    }
}
