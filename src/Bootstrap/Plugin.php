<?php

declare(strict_types=1);

namespace Kaanbal\Bootstrap;

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
        ( new ServiceRegistry() )->registerAll();
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
