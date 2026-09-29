<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Quiz\Application\QuizScoreCalculator;
use PHPUnit\Framework\TestCase;

final class QuizScoreCalculatorTest extends TestCase
{
    /** @dataProvider scores */
    public function testItCalculatesServerSideScores(int $correct, int $total, int $passing, float $expected_score, bool $expected_passed): void
    {
        $score = (new QuizScoreCalculator())->calculate($correct, $total, $passing);

        self::assertSame($expected_score, $score->percentage);
        self::assertSame($expected_passed, $score->passed);
    }

    /** @return array<string, array{int, int, int, float, bool}> */
    public static function scores(): array
    {
        return array(
            'perfect score' => array(10, 10, 80, 100.0, true),
            'passing threshold is inclusive' => array(8, 10, 80, 80.0, true),
            'two thirds below the threshold fails' => array(2, 3, 67, 66.67, false),
            'seven ninths below the threshold fails' => array(7, 9, 78, 77.78, false),
            '159 of 200 below the threshold fails' => array(159, 200, 80, 79.5, false),
            '160 of 200 reaches the threshold' => array(160, 200, 80, 80.0, true),
            '79 of 100 below the threshold fails' => array(79, 100, 80, 79.0, false),
            'below threshold fails' => array(7, 10, 80, 70.0, false),
            'no correct answers' => array(0, 10, 80, 0.0, false),
        );
    }
}
