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

    /**
     * Resolves progress for many students across many courses without an
     * individual progress query per student.
     *
     * @param array<int, list<int>> $user_ids_by_course Course IDs keyed to user IDs.
     * @return array<int, array<int, CourseProgress>> Course IDs keyed to user IDs.
     */
    public function forUsersOnCourses(array $user_ids_by_course): array
    {
        $course_ids = array_values(array_unique(array_filter(array_map('absint', array_keys($user_ids_by_course)))));

        if (array() === $course_ids) {
            return array();
        }

        $curricula = $this->curriculum->forPublishedCourses($course_ids);
        $lesson_ids_by_course = array();
        $lesson_ids = array();

        foreach ($curricula as $course_id => $curriculum) {
            $course_lesson_ids = array();

            foreach ($curriculum['modules'] as $module) {
                foreach ($module['lessons'] as $lesson) {
                    $course_lesson_ids[] = $lesson->ID;
                }
            }

            $lesson_ids_by_course[$course_id] = array_values(array_unique($course_lesson_ids));
            $lesson_ids = array_merge($lesson_ids, $lesson_ids_by_course[$course_id]);
        }

        $user_ids = array();

        foreach ($user_ids_by_course as $course_user_ids) {
            $user_ids = array_merge($user_ids, $course_user_ids);
        }

        $completed_by_user = $this->progress->findCompletedLessonIdsForUsers(
            array_values(array_unique(array_filter(array_map('absint', $user_ids)))),
            array_values(array_unique($lesson_ids)),
        );
        $results = array();

        foreach ($user_ids_by_course as $course_id => $course_user_ids) {
            $course_id = absint($course_id);
            $course_lesson_ids = $lesson_ids_by_course[$course_id] ?? array();
            $course_lesson_lookup = array_fill_keys($course_lesson_ids, true);

            foreach (array_values(array_unique(array_filter(array_map('absint', $course_user_ids)))) as $user_id) {
                $completed = array_values(
                    array_filter(
                        $completed_by_user[$user_id] ?? array(),
                        static fn (int $lesson_id): bool => isset($course_lesson_lookup[$lesson_id])
                    )
                );
                $results[$course_id][$user_id] = new CourseProgress(
                    count($course_lesson_ids),
                    count($completed),
                    $this->calculator->percentage(count($completed), count($course_lesson_ids)),
                    $completed,
                );
            }
        }

        return $results;
    }
}
