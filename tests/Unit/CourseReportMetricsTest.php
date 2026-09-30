<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Reporting\Application\CourseReportMetrics;
use PHPUnit\Framework\TestCase;

final class CourseReportMetricsTest extends TestCase
{
    public function testItExcludesRevokedEnrollmentsFromApprovalRate(): void
    {
        $metrics = new CourseReportMetrics();

        self::assertSame(50, $metrics->approvalRate(5, 5));
        self::assertSame(100, $metrics->approvalRate(0, 5));
        self::assertSame(0, $metrics->approvalRate(0, 0));
    }

    public function testItAveragesOnlyTheProvidedActiveProgressValues(): void
    {
        $metrics = new CourseReportMetrics();

        self::assertSame(75, $metrics->averageProgress(array(100, 50)));
        self::assertSame(0, $metrics->averageProgress(array()));
    }
}
