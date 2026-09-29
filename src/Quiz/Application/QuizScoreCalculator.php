<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

final class QuizScoreCalculator
{
    public function calculate(int $correct, int $total, int $passing_score): QuizScore
    {
        if ($total <= 0) {
            throw new \InvalidArgumentException('A quiz requires at least one question.');
        }

        $correct = max(0, min($correct, $total));
        $percentage = (int) round(($correct / $total) * 100);

        return new QuizScore($correct, $total, $percentage, $percentage >= $passing_score);
    }
}
