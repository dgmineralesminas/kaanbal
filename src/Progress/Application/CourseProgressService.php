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

    /** @param list<int> $course_ids
     * @return array<int, CourseProgress>
     */
    public function forCourses(int $user_id, array $course_ids): array
    {
        $curricula = $this->curriculum->forPublishedCourses($course_ids);
        $lesson_ids = array();

        foreach ($curricula as $curriculum) {
            foreach ($curriculum['modules'] as $module) {
                foreach ($module['lessons'] as $lesson) {
                    $lesson_ids[] = $lesson->ID;
                }
            }
        }

        $completed_ids = $this->progress->findCompletedLessonIds($user_id, $lesson_ids);
        $completed_lookup = array_fill_keys($completed_ids, true);
        $progress = array();

        foreach ($curricula as $course_id => $curriculum) {
            $course_lesson_ids = array();

            foreach ($curriculum['modules'] as $module) {
                foreach ($module['lessons'] as $lesson) {
                    $course_lesson_ids[] = $lesson->ID;
                }
            }

            $course_lesson_ids = array_values(array_unique($course_lesson_ids));
            $course_completed_ids = array_values(array_filter($course_lesson_ids, static fn (int $lesson_id): bool => isset($completed_lookup[$lesson_id])));
            $progress[$course_id] = new CourseProgress(
                count($course_lesson_ids),
                count($course_completed_ids),
                $this->calculator->percentage(count($course_completed_ids), count($course_lesson_ids)),
                $course_completed_ids,
            );
        }

        return $progress;
    }
}
