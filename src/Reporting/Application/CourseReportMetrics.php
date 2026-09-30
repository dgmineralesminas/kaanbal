<?php

declare(strict_types=1);

namespace Kaanbal\Reporting\Application;

final class CourseReportMetrics
{
    public function approvalRate(int $active, int $completed): int
    {
        $relevant = $active + $completed;

        return $relevant <= 0 ? 0 : (int) round(($completed / $relevant) * 100);
    }

    /** @param list<int> $percentages */
    public function averageProgress(array $percentages): int
    {
        return array() === $percentages ? 0 : (int) round(array_sum($percentages) / count($percentages));
    }
}
