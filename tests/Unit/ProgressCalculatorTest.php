<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Progress\Application\ProgressCalculator;
use PHPUnit\Framework\TestCase;

final class ProgressCalculatorTest extends TestCase
{
    /** @dataProvider percentages */
    public function testItCalculatesAWholeNumberPercentage(int $completed, int $total, int $expected): void
    {
        self::assertSame($expected, (new ProgressCalculator())->percentage($completed, $total));
    }

    /** @return array<string, array{int, int, int}> */
    public static function percentages(): array
    {
        return array(
            'empty course' => array(0, 0, 0),
            'no completions' => array(0, 10, 0),
            'partial course' => array(3, 10, 30),
            'all completed' => array(10, 10, 100),
            'more completions than lessons' => array(11, 10, 100),
            'rounded percentage' => array(10, 11, 91),
        );
    }
}
