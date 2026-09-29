<?php

declare(strict_types=1);

namespace Kaanbal\Dashboard\Presentation\Frontend;

use Kaanbal\Access\Presentation\Frontend\TemplateContext;
use Kaanbal\Dashboard\Application\StudentDashboardQuery;

final class DashboardRouter
{
    public const QUERY_VAR = 'kaanbal_student_dashboard';

    public const PATH = '/mis-cursos/';

    public function __construct(
        private readonly StudentDashboardQuery $dashboard,
        private readonly string $templates_path,
    ) {
    }

    public static function url(): string
    {
        return home_url(self::PATH);
    }

    /**
     * Students sign in through the WooCommerce "My account" page used by the
     * theme; wp-login.php is only a fallback when WooCommerce is inactive.
     */
    public static function loginUrl(): string
    {
        if (function_exists('wc_get_page_permalink')) {
            $account_url = wc_get_page_permalink('myaccount');

            if ('' !== $account_url) {
                return $account_url;
            }
        }

        return wp_login_url(self::url());
    }

    /** @param list<string> $query_vars
     * @return list<string>
     */
    public function queryVars(array $query_vars): array
    {
        $query_vars[] = self::QUERY_VAR;

        return array_values(array_unique($query_vars));
    }

    public function template(string $template): string
    {
        if ('1' !== (string) get_query_var(self::QUERY_VAR)) {
            return $template;
        }

        $response = $this->resolve(get_current_user_id());
        TemplateContext::replace($response['context']);

        if (403 === $response['status']) {
            status_header(403);
            nocache_headers();
        }

        return $this->templates_path . $response['template'] . '.php';
    }

    /** @return array{template: 'dashboard'|'dashboard-access-denied', status: 200|403, context: array<string, mixed>} */
    public function resolve(int $user_id): array
    {
        if ($user_id <= 0) {
            return array(
                'template' => 'dashboard-access-denied',
                'status'   => 403,
                'context'  => array('login_url' => self::loginUrl()),
            );
        }

        return array(
            'template' => 'dashboard',
            'status'   => 200,
            'context'  => array('dashboard' => $this->dashboard->forUser($user_id)),
        );
    }
}
