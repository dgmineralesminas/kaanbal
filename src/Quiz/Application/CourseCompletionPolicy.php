<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

final class CourseCompletionPolicy
{
    public function isComplete(int $completed_lessons, int $total_lessons, bool $requires_quiz, bool $has_passed_quiz): bool
    {
        if ($total_lessons <= 0 || $completed_lessons !== $total_lessons) {
            return false;
        }

        return ! $requires_quiz || $has_passed_quiz;
    }
}
