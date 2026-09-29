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

    public static function fromDashboardData(bool $required, ?int $quiz_id, bool $quiz_valid, int $percentage, bool $passed, int $attempts_used, ?int $max_attempts): self
    {
        if (! $required) {
            return self::NotRequired;
        }

        // Missing or invalid quiz (SPEC-006 validity rule): nothing to present.
        if (null === $quiz_id || ! $quiz_valid) {
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

    /**
     * An approved enrollment is the formal academic result (RB-004), so a
     * completed course never advertises a pending quiz; only a passed quiz
     * is still informative there.
     */
    public function isVisibleFor(string $enrollment_status): bool
    {
        if (self::NotRequired === $this || self::Unavailable === $this) {
            return false;
        }

        if ('completed' === $enrollment_status) {
            return self::Passed === $this;
        }

        return true;
    }

    /** Attempts are only meaningful while the student can still present the quiz. */
    public function showsAttempts(): bool
    {
        return self::Available === $this || self::FailedCanRetry === $this;
    }
}
