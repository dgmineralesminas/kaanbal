<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Quiz\Application\CourseCompletionPolicy;
use PHPUnit\Framework\TestCase;

final class CourseCompletionPolicyTest extends TestCase
{
    /** @dataProvider completionStates */
    public function testItAppliesTheCourseCompletionPolicy(int $completed, int $total, bool $requires_quiz, bool $passed_quiz, bool $expected): void
    {
        self::assertSame($expected, (new CourseCompletionPolicy())->isComplete($completed, $total, $requires_quiz, $passed_quiz));
    }

    /** @return array<string, array{int, int, bool, bool, bool}> */
    public static function completionStates(): array
    {
        return array(
            'incomplete lessons cannot complete' => array(9, 10, false, false, false),
            'empty course cannot complete' => array(0, 0, false, false, false),
            'complete lessons without quiz passes' => array(10, 10, false, false, true),
            'quiz course without pass remains active' => array(10, 10, true, false, false),
            'quiz course passes after quiz pass' => array(10, 10, true, true, true),
        );
    }
}
