<?php

declare(strict_types=1);

namespace Kaanbal\Progress\Application;

final class CourseProgress
{
    /** @param list<int> $completed_lesson_ids */
    public function __construct(
        public readonly int $total_lessons,
        public readonly int $completed_lessons,
        public readonly int $percentage,
        public readonly array $completed_lesson_ids,
    ) {
    }

    public function isLessonCompleted(int $lesson_id): bool
    {
        return in_array($lesson_id, $this->completed_lesson_ids, true);
    }
}
