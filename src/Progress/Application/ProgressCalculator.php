<?php

declare(strict_types=1);

namespace Kaanbal\Progress\Application;

final class ProgressCalculator
{
    public function percentage(int $completed_lessons, int $total_lessons): int
    {
        if ($total_lessons <= 0 || $completed_lessons <= 0) {
            return 0;
        }

        if ($completed_lessons >= $total_lessons) {
            return 100;
        }

        return min(99, (int) round(($completed_lessons / $total_lessons) * 100));
    }
}
