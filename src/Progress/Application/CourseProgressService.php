<?php

declare(strict_types=1);

namespace Kaanbal\Progress\Application;

use Kaanbal\Courses\Application\CurriculumService;

final class CourseProgressService
{
    public function __construct(
        private readonly CurriculumService $curriculum,
        private readonly LessonProgressStore $progress,
        private readonly ProgressCalculator $calculator = new ProgressCalculator(),
    ) {
    }

    public function forCourse(int $user_id, int $course_id): CourseProgress
    {
        $curriculum = $this->curriculum->forPublishedCourse($course_id);

        if (! is_array($curriculum)) {
            return new CourseProgress(0, 0, 0, array());
        }

        $lesson_ids = array();

        foreach ($curriculum['modules'] as $module) {
            foreach ($module['lessons'] as $lesson) {
                $lesson_ids[] = $lesson->ID;
            }
        }

        $lesson_ids = array_values(array_unique($lesson_ids));
        $completed = $this->progress->findCompletedLessonIds($user_id, $lesson_ids);

        return new CourseProgress(
            count($lesson_ids),
            count($completed),
            $this->calculator->percentage(count($completed), count($lesson_ids)),
            $completed,
        );
    }
}
