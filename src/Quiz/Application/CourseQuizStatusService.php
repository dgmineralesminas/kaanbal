<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

use Kaanbal\Quiz\Infrastructure\AnswerRepository;
use Kaanbal\Quiz\Infrastructure\QuestionRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class CourseQuizStatusService
{
    public function __construct(
        private readonly QuizRepository $quizzes,
        private readonly QuestionRepository $questions,
        private readonly AnswerRepository $answers,
        private readonly QuizEligibilityService $eligibility,
        private readonly QuizValidityService $validity,
    ) {
    }

    /** @return array{requires_quiz: bool, certificate_enabled: bool, result: string, quiz: \WP_Post|null, questions: list<array{id: int, quiz_id: int, question_text: string, position: int, answers: list<array{id: int, question_id: int, answer_text: string, is_correct: bool}>}>} */
    public function forCourse(int $user_id, int $course_id): array
    {
        $requires_quiz = $this->quizzes->requiresQuiz($course_id);
        $quiz = $this->quizzes->findForCourse($course_id);
        if (! $requires_quiz) {
            $result = QuizEligibilityResult::QuizNotRequired;
        } elseif (! $quiz instanceof \WP_Post || ! $this->validity->isValid($quiz->ID)) {
            // A missing or invalid quiz is never presented, whatever the progress.
            $result = QuizEligibilityResult::InvalidQuiz;
        } else {
            $result = $this->eligibility->check($user_id, $course_id, $quiz->ID);
        }

        $questions = $quiz instanceof \WP_Post && $result->isEligible() ? $this->questions->activeForQuiz($quiz->ID) : array();
        $answer_sets = $this->answers->forQuestions(array_column($questions, 'id'));

        foreach ($questions as &$question) {
            $question['answers'] = $answer_sets[$question['id']] ?? array();
        }
        unset($question);

        return array('requires_quiz' => $requires_quiz, 'certificate_enabled' => $this->quizzes->certificateEnabled($course_id), 'result' => $result->value, 'quiz' => $quiz, 'questions' => $questions);
    }
}
