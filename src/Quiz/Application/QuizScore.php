<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Application;

final class QuizScore
{
    public function __construct(
        public readonly int $correct,
        public readonly int $total,
        public readonly int $percentage,
        public readonly bool $passed,
    ) {
    }
}
