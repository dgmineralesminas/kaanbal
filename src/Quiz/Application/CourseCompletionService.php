<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Progress\Application\CourseProgressService;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class CourseCompletionService
{
    public function __construct(
        private readonly CourseProgressService $progress,
        private readonly QuizRepository $quizzes,
        private readonly QuizAttemptRepository $attempts,
        private readonly EnrollmentRepository $enrollments,
        private readonly CourseCompletionPolicy $policy = new CourseCompletionPolicy(),
    ) {
    }

    public function evaluate(int $user_id, int $course_id): bool
    {
        $progress = $this->progress->forCourse($user_id, $course_id);
        $quiz = $this->quizzes->findForCourse($course_id);
        $requires_quiz = $this->quizzes->requiresQuiz($course_id);
        $has_passed_quiz = $quiz instanceof \WP_Post && $this->attempts->hasPassed($user_id, $quiz->ID);

        if (! $this->policy->isComplete($progress->completed_lessons, $progress->total_lessons, $requires_quiz, $has_passed_quiz)) {
            return false;
        }

        return $this->enrollments->completeByUserAndCourse($user_id, $course_id);
    }
}
