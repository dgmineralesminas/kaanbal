<?php

declare(strict_types=1);

namespace Kaanbal\WooCommerce\Presentation\Frontend;

use Kaanbal\Dashboard\Presentation\Frontend\DashboardRouter;

/**
 * Adds a "Mis cursos" entry to the WooCommerce "My account" navigation that
 * points to the Kaanbal student dashboard. It is not a WooCommerce endpoint:
 * the URL is replaced with the dashboard URL.
 */
final class AccountMenuLinks
{
    public const MENU_KEY = 'kaanbal-mis-cursos';

    /**
     * @param array<string, string> $items
     * @return array<string, string>
     */
    public function menuItems(array $items): array
    {
        unset($items[self::MENU_KEY]);
        $link = array(self::MENU_KEY => __('Mis cursos', 'kaanbal'));

        if (! array_key_exists('dashboard', $items)) {
            return $link + $items;
        }

        $menu = array();

        foreach ($items as $key => $label) {
            $menu[$key] = $label;

            if ('dashboard' === $key) {
                $menu += $link;
            }
        }

        return $menu;
    }

    public function endpointUrl(string $url, string $endpoint): string
    {
        return self::MENU_KEY === $endpoint ? DashboardRouter::url() : $url;
    }
}
