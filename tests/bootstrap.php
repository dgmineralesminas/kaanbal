<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$GLOBALS['kaanbal_test_options'] = array();

if (! function_exists('get_option')) {
    function get_option(string $key, mixed $fallback = false): mixed
    {
        return $GLOBALS['kaanbal_test_options'][$key] ?? $fallback;
    }
}

if (! function_exists('update_option')) {
    function update_option(string $key, mixed $value, bool $autoload = false): bool
    {
        $GLOBALS['kaanbal_test_options'][$key] = $value;

        return true;
    }
}
