<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\WooCommerce\WooCommerceModule;
use PHPUnit\Framework\TestCase;

final class WooCommerceModuleTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['kaanbal_test_actions'] = array();
    }

    public function testItDefersRegistrationWhenWooCommerceIsUnavailable(): void
    {
        $module = new WooCommerceModule();

        $module->register();
        $module->registerWhenWooCommerceIsAvailable();

        self::assertArrayHasKey('plugins_loaded', $GLOBALS['kaanbal_test_actions']);
        self::assertCount(1, $GLOBALS['kaanbal_test_actions']);
    }
}
