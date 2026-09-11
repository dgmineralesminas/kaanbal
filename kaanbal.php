<?php

/**
 * Plugin Name: Kaanbal
 * Description: LMS foundation for creating and delivering online courses.
 * Version: 0.1.0
 * Requires at least: 7.1
 * Requires PHP: 8.1
 * Text Domain: kaanbal
 * Domain Path: /languages
 *
 * @package Kaanbal
 */

defined('ABSPATH') || exit;

define('KAANBAL_FILE', __FILE__);
define('KAANBAL_PATH', plugin_dir_path(__FILE__));

$kaanbal_autoload = KAANBAL_PATH . 'vendor/autoload.php';

if (! file_exists($kaanbal_autoload)) {
    add_action(
        'admin_notices',
        static function (): void {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__(
                    'Kaanbal requires its Composer dependencies. Run composer install in the plugin directory.',
                    'kaanbal'
                )
            );
        }
    );

    return;
}

require_once $kaanbal_autoload;

define('KAANBAL_VERSION', Kaanbal\Bootstrap\Version::PLUGIN);
define('KAANBAL_DB_VERSION', Kaanbal\Bootstrap\Version::DATABASE_SCHEMA);

register_activation_hook(KAANBAL_FILE, array( Kaanbal\Bootstrap\Activator::class, 'activate' ));
register_deactivation_hook(KAANBAL_FILE, array( Kaanbal\Bootstrap\Deactivator::class, 'deactivate' ));

Kaanbal\Bootstrap\Plugin::boot();
