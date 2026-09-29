<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

use Kaanbal\Quiz\Infrastructure\AnswerRepository;
use Kaanbal\Quiz\Infrastructure\QuestionRepository;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class QuizSubmissionService
{
    public function __construct(
        private readonly QuizEligibilityService $eligibility,
        private readonly QuizRepository $quizzes,
        private readonly QuestionRepository $questions,
        private readonly AnswerRepository $answers,
        private readonly QuizAttemptRepository $attempts,
        private readonly QuizScoreCalculator $scores,
        private readonly CourseCompletionService $completion,
    ) {
    }

    /** @param array<int|string, int|string> $submitted_answers */
    public function submit(int $user_id, int $course_id, int $quiz_id, array $submitted_answers): QuizSubmissionResult
    {
        if (! $this->eligibility->check($user_id, $course_id, $quiz_id)->isEligible()) {
            return QuizSubmissionResult::Rejected;
        }

        $questions = $this->questions->activeForQuiz($quiz_id);
        $answer_sets = $this->answers->forQuestions(array_column($questions, 'id'));
        $attempt_answers = array();
        $correct = 0;

        foreach ($questions as $question) {
            $question_id = $question['id'];
            $selected_id = isset($submitted_answers[$question_id]) ? absint($submitted_answers[$question_id]) : 0;
            $selected = null;

            foreach ($answer_sets[$question_id] ?? array() as $answer) {
                if ($answer['id'] === $selected_id) {
                    $selected = $answer;
                    break;
                }
            }

            if (! is_array($selected)) {
                return QuizSubmissionResult::Rejected;
            }

            $is_correct = $selected['is_correct'];
            $correct += $is_correct ? 1 : 0;
            $attempt_answers[] = array('question_id' => $question_id, 'answer_id' => $selected['id'], 'is_correct' => $is_correct, 'question_text' => $question['question_text'], 'answer_text' => $selected['answer_text']);
        }

        $score = $this->scores->calculate($correct, count($questions), $this->quizzes->passingScore($quiz_id));

        try {
            $this->attempts->record($user_id, $course_id, $quiz_id, $this->quizzes->maxAttempts($quiz_id), $score, $attempt_answers);
        } catch (\DomainException) {
            return QuizSubmissionResult::Rejected;
        }

        if ($score->passed) {
            $this->completion->evaluate($user_id, $course_id);
        }

        return $score->passed ? QuizSubmissionResult::Passed : QuizSubmissionResult::Failed;
    }
}
