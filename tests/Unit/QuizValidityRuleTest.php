<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Quiz\Application\QuizValidityRule;
use PHPUnit\Framework\TestCase;

final class QuizValidityRuleTest extends TestCase
{
    /**
     * @dataProvider quizzes
     * @param list<array{answers: int, correct: int}> $questions
     */
    public function testItAppliesTheSpec006ValidityRule(array $questions, bool $expected): void
    {
        self::assertSame($expected, (new QuizValidityRule())->isValidQuiz($questions));
    }

    /** @return array<string, array{list<array{answers: int, correct: int}>, bool}> */
    public static function quizzes(): array
    {
        return array(
            'no questions'                  => array(array(), false),
            'question with one answer'      => array(array(array('answers' => 1, 'correct' => 1)), false),
            'question without correct'      => array(array(array('answers' => 3, 'correct' => 0)), false),
            'question with two correct'     => array(array(array('answers' => 3, 'correct' => 2)), false),
            'one invalid among valid'       => array(array(array('answers' => 2, 'correct' => 1), array('answers' => 4, 'correct' => 0)), false),
            'single valid question'         => array(array(array('answers' => 2, 'correct' => 1)), true),
            'several valid questions'       => array(array(array('answers' => 2, 'correct' => 1), array('answers' => 4, 'correct' => 1)), true),
        );
    }
}
