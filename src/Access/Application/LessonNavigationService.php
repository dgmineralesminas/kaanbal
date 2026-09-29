<?php

declare(strict_types=1);

namespace Kaanbal\Access\Application;

final class LessonNavigationService
{
    /**
     * @param array{course: \WP_Post, modules: list<array{module: \WP_Post, lessons: list<\WP_Post>}>} $curriculum
     * @return array{previous: \WP_Post|null, next: \WP_Post|null}
     */
    public function forLesson(int $lesson_id, array $curriculum): array
    {
        $lessons = array();

        foreach ($curriculum['modules'] as $module) {
            foreach ($module['lessons'] as $lesson) {
                $lessons[] = $lesson;
            }
        }

        foreach ($lessons as $index => $lesson) {
            if ($lesson_id !== $lesson->ID) {
                continue;
            }

            return array(
                'previous' => $lessons[$index - 1] ?? null,
                'next'     => $lessons[$index + 1] ?? null,
            );
        }

        return array('previous' => null, 'next' => null);
    }
}
