<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Bootstrap\Requirements;
use PHPUnit\Framework\TestCase;

final class RequirementsTest extends TestCase
{
    public function testItAcceptsTheMinimumSupportedEnvironment(): void
    {
        $result = ( new Requirements() )->evaluate('8.1', '7.1');

        self::assertTrue($result->isCompatible());
        self::assertSame(array(), $result->errors());
    }

    public function testItReportsEachUnsatisfiedRequirement(): void
    {
        $result = ( new Requirements() )->evaluate('8.0', '7.0');

        self::assertFalse($result->isCompatible());
        self::assertCount(2, $result->errors());
    }
}
