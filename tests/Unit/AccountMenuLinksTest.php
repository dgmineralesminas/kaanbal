<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\WooCommerce\Presentation\Frontend\AccountMenuLinks;
use PHPUnit\Framework\TestCase;

final class AccountMenuLinksTest extends TestCase
{
    public function testItPlacesMisCursosRightAfterTheAccountDashboard(): void
    {
        $items = (new AccountMenuLinks())->menuItems(array('dashboard' => 'Escritorio', 'orders' => 'Pedidos', 'customer-logout' => 'Salir'));

        self::assertSame(array('dashboard', AccountMenuLinks::MENU_KEY, 'orders', 'customer-logout'), array_keys($items));
        self::assertSame('Mis cursos', $items[AccountMenuLinks::MENU_KEY]);
    }

    public function testItPlacesMisCursosFirstWhenThereIsNoDashboardItem(): void
    {
        $items = (new AccountMenuLinks())->menuItems(array('orders' => 'Pedidos'));

        self::assertSame(array(AccountMenuLinks::MENU_KEY, 'orders'), array_keys($items));
    }

    public function testItLeavesOtherEndpointUrlsUntouched(): void
    {
        self::assertSame('https://example.test/my-account/orders/', (new AccountMenuLinks())->endpointUrl('https://example.test/my-account/orders/', 'orders'));
    }
}
