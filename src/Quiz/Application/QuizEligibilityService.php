<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

use Kaanbal\Access\Application\CourseAccessService;
use Kaanbal\Progress\Application\CourseProgressService;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;
use Kaanbal\Quiz\Infrastructure\QuestionRepository;
use Kaanbal\Quiz\Infrastructure\AnswerRepository;

final class QuizEligibilityService
{
    public function __construct(
        private readonly CourseAccessService $access,
        private readonly CourseProgressService $progress,
        private readonly QuizRepository $quizzes,
        private readonly QuestionRepository $questions,
        private readonly AnswerRepository $answers,
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

        if (! $this->quizzes->belongsToCourse($quiz_id, $course_id) || ! $this->hasValidQuestions($quiz_id)) {
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

    private function hasValidQuestions(int $quiz_id): bool
    {
        $questions = $this->questions->activeForQuiz($quiz_id);

        if (array() === $questions) {
            return false;
        }

        $answer_sets = $this->answers->forQuestions(array_column($questions, 'id'));

        foreach ($questions as $question) {
            $answers = $answer_sets[$question['id']] ?? array();
            $correct = array_filter($answers, static fn (array $answer): bool => $answer['is_correct']);

            if (count($answers) < 2 || 1 !== count($correct)) {
                return false;
            }
        }

        return true;
    }
}
