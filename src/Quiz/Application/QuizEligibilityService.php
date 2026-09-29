<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

use Kaanbal\Access\Application\CourseAccessService;
use Kaanbal\Progress\Application\CourseProgressService;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class QuizEligibilityService
{
    public function __construct(
        private readonly CourseAccessService $access,
        private readonly CourseProgressService $progress,
        private readonly QuizRepository $quizzes,
        private readonly QuizValidityService $validity,
        private readonly QuizAttemptRepository $attempts,
    ) {
    }

    public function check(int $user_id, int $course_id, int $quiz_id): QuizEligibilityResult
    {
        if (! $this->access->canAccessCourse($user_id, $course_id)) {
            return QuizEligibilityResult::AccessDenied;
        }

        if (! $this->quizzes->requiresQuiz($course_id)) {
            return QuizEligibilityResult::QuizNotRequired;
        }

        $progress = $this->progress->forCourse($user_id, $course_id);

        if ($progress->total_lessons <= 0 || $progress->completed_lessons !== $progress->total_lessons) {
            return QuizEligibilityResult::LessonsIncomplete;
        }

        if (! $this->quizzes->belongsToCourse($quiz_id, $course_id) || ! $this->validity->isValid($quiz_id)) {
            return QuizEligibilityResult::InvalidQuiz;
        }

        if ($this->attempts->hasPassed($user_id, $quiz_id)) {
            return QuizEligibilityResult::AlreadyPassed;
        }

        $max_attempts = $this->quizzes->maxAttempts($quiz_id);

        return null !== $max_attempts && $this->attempts->countAttempts($user_id, $quiz_id) >= $max_attempts
            ? QuizEligibilityResult::AttemptsExhausted
            : QuizEligibilityResult::Eligible;
    }
}
