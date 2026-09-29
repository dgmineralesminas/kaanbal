<?php

declare(strict_types=1);

namespace Kaanbal\Access;

use Kaanbal\Bootstrap\BootableService;
use Kaanbal\Access\Presentation\Frontend\FrontendRouter;

final class PlayerModule implements BootableService
{
    public function register(): void
    {
        $router = new FrontendRouter();

        add_action('init', array(self::class, 'registerRewriteRules'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueStyles'));
        add_filter('query_vars', array($router, 'queryVars'));
        add_filter('template_include', array($router, 'template'));
    }

    public function enqueueStyles(): void
    {
        if ('' === (string) get_query_var(FrontendRouter::COURSE_QUERY_VAR)) {
            return;
        }

        $plugin_path = dirname(__DIR__, 2);
        $style_path  = $plugin_path . '/assets/css/player.css';

        wp_enqueue_style(
            'kaanbal-player',
            plugins_url('assets/css/player.css', $plugin_path . '/kaanbal.php'),
            array(),
            (string) filemtime($style_path)
        );
    }

    public static function registerRewriteRules(): void
    {
        add_rewrite_rule(
            '^courses/([^/]+)/lesson/([^/]+)/?$',
            'index.php?' . FrontendRouter::COURSE_QUERY_VAR . '=$matches[1]&' . FrontendRouter::LESSON_QUERY_VAR . '=$matches[2]',
            'top'
        );
        add_rewrite_rule(
            '^courses/([^/]+)/?$',
            'index.php?' . FrontendRouter::COURSE_QUERY_VAR . '=$matches[1]',
            'top'
        );
    }
}
