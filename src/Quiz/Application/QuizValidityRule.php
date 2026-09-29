<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

/**
 * Single definition of a presentable final quiz (SPEC-006): at least one
 * active question, and every active question has two or more answers with
 * exactly one marked as correct.
 */
final class QuizValidityRule
{
    public function isValidQuestion(int $answer_count, int $correct_count): bool
    {
        return $answer_count >= 2 && 1 === $correct_count;
    }

    /** @param list<array{answers: int, correct: int}> $questions */
    public function isValidQuiz(array $questions): bool
    {
        if (array() === $questions) {
            return false;
        }

        foreach ($questions as $question) {
            if (! $this->isValidQuestion($question['answers'], $question['correct'])) {
                return false;
            }
        }

        return true;
    }
}
