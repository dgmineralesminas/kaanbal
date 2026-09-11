<?php

declare(strict_types=1);

$wordpress_path = getenv('KAANBAL_WP_PATH');

if (! is_string($wordpress_path) || '' === $wordpress_path) {
    fwrite(STDERR, "KAANBAL_WP_PATH must point to the WordPress root.\n");
    exit(1);
}

$wordpress_bootstrap = rtrim($wordpress_path, DIRECTORY_SEPARATOR) . '/wp-load.php';

if (! is_file($wordpress_bootstrap)) {
    fwrite(STDERR, "wp-load.php was not found at KAANBAL_WP_PATH.\n");
    exit(1);
}

require_once $wordpress_bootstrap;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin_file = dirname(__DIR__, 2) . '/kaanbal.php';
$option_name = 'kaanbal_db_version';
$missing     = new stdClass();
$previous    = get_option($option_name, $missing);

try {
    require_once $plugin_file;

    $plugins = get_plugins();

    if (! isset($plugins['kaanbal/kaanbal.php'])) {
        throw new RuntimeException('Kaanbal is not listed as a WordPress plugin.');
    }

    if ('0.1.0' !== $plugins['kaanbal/kaanbal.php']['Version']) {
        throw new RuntimeException('Kaanbal plugin metadata has an unexpected version.');
    }

    Kaanbal\Bootstrap\Activator::activate();
    Kaanbal\Bootstrap\Activator::activate();

    if (1 !== (int) get_option($option_name)) {
        throw new RuntimeException('Kaanbal activation did not persist the expected schema version.');
    }

    Kaanbal\Bootstrap\Deactivator::deactivate();

    if (1 !== (int) get_option($option_name)) {
        throw new RuntimeException('Kaanbal deactivation removed the schema version.');
    }

    if (class_exists('WooCommerce')) {
        throw new RuntimeException('This integration fixture expects WooCommerce to be inactive.');
    }

    echo "WordPress lifecycle integration: PASS\n";
} finally {
    if ($missing === $previous) {
        delete_option($option_name);
    } else {
        update_option($option_name, $previous, false);
    }
}
