<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Application;

use Kaanbal\Courses\Infrastructure\CurriculumRepository;

final class CurriculumService
{
    public function __construct(private readonly CurriculumRepository $repository)
    {
    }

    /** @return array{course: \WP_Post, modules: list<array{module: \WP_Post, lessons: list<\WP_Post>}>}|null */
    public function forCourse(int $course_id): ?array
    {
        $course = get_post($course_id);

        if (! $course instanceof \WP_Post || 'kaanbal_course' !== $course->post_type) {
            return null;
        }

        $modules    = $this->repository->modulesForCourse($course_id);
        $module_ids = array_map(static fn (\WP_Post $module): int => $module->ID, $modules);
        $lessons    = $this->repository->lessonsForModules($module_ids);
        $by_module  = array();

        foreach ($lessons as $lesson) {
            $by_module[(int) get_post_meta($lesson->ID, CurriculumRepository::MODULE_ID_META, true)][] = $lesson;
        }

        return array(
            'course'  => $course,
            'modules' => array_map(
                static fn (\WP_Post $module): array => array(
                    'module'  => $module,
                    'lessons' => $by_module[$module->ID] ?? array(),
                ),
                $modules
            ),
        );
    }

    /** @return array{module: \WP_Post, course: \WP_Post}|null */
    public function hierarchyForLesson(int $lesson_id): ?array
    {
        $module = $this->repository->moduleForLesson($lesson_id);

        if (! $module instanceof \WP_Post) {
            return null;
        }

        $course = $this->repository->courseForModule($module->ID);

        return $course instanceof \WP_Post ? array('module' => $module, 'course' => $course) : null;
    }
}
