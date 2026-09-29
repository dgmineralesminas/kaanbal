<?php

declare(strict_types=1);

namespace Kaanbal\Dashboard\Application;

enum QuizDashboardState: string
{
    case NotRequired = 'not_required';
    case Locked = 'locked';
    case Available = 'available';
    case FailedCanRetry = 'failed_can_retry';
    case Passed = 'passed';
    case NoAttemptsLeft = 'no_attempts_left';
    case Unavailable = 'unavailable';

    public static function fromDashboardData(bool $required, ?int $quiz_id, int $percentage, bool $passed, int $attempts_used, ?int $max_attempts): self
    {
        if (! $required) {
            return self::NotRequired;
        }

        if (null === $quiz_id) {
            return self::Unavailable;
        }

        if ($passed) {
            return self::Passed;
        }

        if ($percentage < 100) {
            return self::Locked;
        }

        if (null !== $max_attempts && $attempts_used >= $max_attempts) {
            return self::NoAttemptsLeft;
        }

        return $attempts_used > 0 ? self::FailedCanRetry : self::Available;
    }
}
