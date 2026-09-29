<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Dashboard\Application\QuizDashboardState;
use PHPUnit\Framework\TestCase;

final class QuizDashboardStateTest extends TestCase
{
    /** @dataProvider dashboardStates */
    public function testItDerivesTheQuizStateForTheDashboard(bool $required, ?int $quiz_id, int $percentage, bool $passed, int $attempts_used, ?int $max_attempts, QuizDashboardState $expected): void
    {
        self::assertSame($expected, QuizDashboardState::fromDashboardData($required, $quiz_id, $percentage, $passed, $attempts_used, $max_attempts));
    }

    /** @return array<string, array{bool, int|null, int, bool, int, int|null, QuizDashboardState}> */
    public static function dashboardStates(): array
    {
        return array(
            'course does not require a quiz' => array(false, null, 30, false, 0, null, QuizDashboardState::NotRequired),
            'required quiz is not configured' => array(true, null, 100, false, 0, null, QuizDashboardState::Unavailable),
            'incomplete lessons lock the quiz' => array(true, 12, 99, false, 0, 2, QuizDashboardState::Locked),
            'quiz is available before an attempt' => array(true, 12, 100, false, 0, 2, QuizDashboardState::Available),
            'failed quiz can be retried' => array(true, 12, 100, false, 1, 2, QuizDashboardState::FailedCanRetry),
            'attempts are exhausted' => array(true, 12, 100, false, 2, 2, QuizDashboardState::NoAttemptsLeft),
            'passed quiz wins over historical progress' => array(true, 12, 0, true, 1, 2, QuizDashboardState::Passed),
        );
    }
}
