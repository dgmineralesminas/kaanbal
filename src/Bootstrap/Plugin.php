<?php

declare(strict_types=1);

namespace Kaanbal\Bootstrap;

use Kaanbal\Courses\CoursesModule;
use Kaanbal\Access\PlayerModule;
use Kaanbal\Progress\ProgressModule;
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

        try {
            Activator::installSchema();
        } catch (\RuntimeException $exception) {
            // Boot runs on every request: a failed migration must not take the site down.
            // The schema version is not stored, so the migration is retried on the next request.
            self::registerSchemaNotice($exception);
        }

        $services = new ServiceRegistry();
        $services->add(new CoursesModule());
        $services->add(new PlayerModule());
        $services->add(new ProgressModule());
        $services->add(new WooCommerceModule());
        $services->registerAll();
    }

    private static function registerSchemaNotice(\RuntimeException $exception): void
    {
        add_action(
            'admin_notices',
            static function () use ($exception): void {
                if (! current_user_can('activate_plugins')) {
                    return;
                }

                printf(
                    '<div class="notice notice-error"><p>%s</p><p><code>%s</code></p></div>',
                    esc_html__('Kaanbal could not create or update its database tables. Course purchases will not grant access until this is fixed. Check that the database user can create tables.', 'kaanbal'),
                    esc_html($exception->getMessage())
                );
            }
        );
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
