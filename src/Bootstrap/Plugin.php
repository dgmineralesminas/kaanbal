<?php

declare(strict_types=1);

namespace Kaanbal\Bootstrap;

use Kaanbal\Courses\CoursesModule;
use Kaanbal\WooCommerce\WooCommerceModule;

final class Plugin
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        $requirements = ( new Requirements() )->evaluateCurrentEnvironment();

        if (! $requirements->isCompatible()) {
            self::registerRequirementsNotice($requirements);
            return;
        }

        self::$booted = true;
        Activator::installSchema();
        $services = new ServiceRegistry();
        $services->add(new CoursesModule());
        $services->add(new WooCommerceModule());
        $services->registerAll();
    }

    private static function registerRequirementsNotice(RequirementResult $requirements): void
    {
        add_action(
            'admin_notices',
            static function () use ($requirements): void {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    esc_html(implode(' ', $requirements->errors()))
                );
            }
        );
    }
}
