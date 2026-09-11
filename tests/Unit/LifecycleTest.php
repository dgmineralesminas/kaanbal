<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Bootstrap\Activator;
use Kaanbal\Bootstrap\Deactivator;
use PHPUnit\Framework\TestCase;

final class LifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['kaanbal_test_options'] = array();
    }

    public function testActivationStoresSchemaVersionOnlyOnce(): void
    {
        Activator::activate();
        Activator::activate();

        self::assertSame(1, $GLOBALS['kaanbal_test_options']['kaanbal_db_version']);
        self::assertCount(1, $GLOBALS['kaanbal_test_options']);
    }

    public function testDeactivationPreservesTheInstalledSchemaVersion(): void
    {
        Activator::activate();
        Deactivator::deactivate();

        self::assertSame(1, $GLOBALS['kaanbal_test_options']['kaanbal_db_version']);
    }
}
