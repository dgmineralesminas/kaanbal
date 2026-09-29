<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$GLOBALS['kaanbal_test_options'] = array();
$GLOBALS['kaanbal_test_actions'] = array();

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

if (! function_exists('wp_parse_url')) {
    function wp_parse_url(string $url, int $component = -1): array|string|int|null|false
    {
        return parse_url($url, $component);
    }
}

if (! function_exists('add_action')) {
    function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        $GLOBALS['kaanbal_test_actions'][$hook_name][] = array(
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );

        return true;
    }
}

if (! function_exists('add_filter')) {
    function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return add_action($hook_name, $callback, $priority, $accepted_args);
    }
}

if (! function_exists('add_rewrite_rule')) {
    function add_rewrite_rule(string $regex, string $query, string $after = 'bottom'): void
    {
        $GLOBALS['kaanbal_test_rewrite_rules'][] = array(
            'regex' => $regex,
            'query' => $query,
            'after' => $after,
        );
    }
}

if (! function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules(bool $hard = true): void
    {
        // WordPress persists rewrite rules. Unit tests only need this call to be safe.
    }
}

if (! function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}
