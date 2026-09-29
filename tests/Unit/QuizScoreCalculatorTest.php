<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Quiz\Application\QuizScoreCalculator;
use PHPUnit\Framework\TestCase;

final class QuizScoreCalculatorTest extends TestCase
{
    /** @dataProvider scores */
    public function testItCalculatesServerSideScores(int $correct, int $total, int $passing, int $expected_score, bool $expected_passed): void
    {
        $score = (new QuizScoreCalculator())->calculate($correct, $total, $passing);

        self::assertSame($expected_score, $score->percentage);
        self::assertSame($expected_passed, $score->passed);
    }

    /** @return array<string, array{int, int, int, int, bool}> */
    public static function scores(): array
    {
        return array(
            'perfect score' => array(10, 10, 80, 100, true),
            'passing threshold is inclusive' => array(8, 10, 80, 80, true),
            'below threshold fails' => array(7, 10, 80, 70, false),
            'no correct answers' => array(0, 10, 80, 0, false),
        );
    }
}
