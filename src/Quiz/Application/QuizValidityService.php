<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

use Kaanbal\Quiz\Infrastructure\QuestionRepository;

final class QuizValidityService
{
    public function __construct(
        private readonly QuestionRepository $questions,
        private readonly QuizValidityRule $rule = new QuizValidityRule(),
    ) {
    }

    public function isValid(int $quiz_id): bool
    {
        return array() !== $this->validQuizIds(array($quiz_id));
    }

    /**
     * Resolves validity for many quizzes with a single aggregated query.
     *
     * @param list<int> $quiz_ids
     * @return list<int>
     */
    public function validQuizIds(array $quiz_ids): array
    {
        $quiz_ids = array_values(array_unique(array_filter(array_map('absint', $quiz_ids))));

        if (array() === $quiz_ids) {
            return array();
        }

        $stats = $this->questions->answerStatsForQuizzes($quiz_ids);

        return array_values(
            array_filter(
                $quiz_ids,
                fn (int $quiz_id): bool => $this->rule->isValidQuiz($stats[$quiz_id] ?? array())
            )
        );
    }
}
