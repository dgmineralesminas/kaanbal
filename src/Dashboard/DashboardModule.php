<?php

declare(strict_types=1);

namespace Kaanbal\Dashboard;

use Kaanbal\Bootstrap\BootableService;
use Kaanbal\Courses\Application\CurriculumService;
use Kaanbal\Courses\Infrastructure\CurriculumRepository;
use Kaanbal\Dashboard\Application\StudentDashboardQuery;
use Kaanbal\Dashboard\Presentation\Frontend\DashboardRouter;
use Kaanbal\Enrollment\Infrastructure\EnrollmentRepository;
use Kaanbal\Progress\Application\CourseProgressService;
use Kaanbal\Progress\Infrastructure\LessonProgressRepository;
use Kaanbal\Quiz\Application\QuizValidityService;
use Kaanbal\Quiz\Infrastructure\QuestionRepository;
use Kaanbal\Quiz\Infrastructure\QuizAttemptRepository;
use Kaanbal\Quiz\Infrastructure\QuizRepository;

final class DashboardModule implements BootableService
{
    private const REWRITE_VERSION = '1';

    public function register(): void
    {
        $router = new DashboardRouter($this->query(), dirname(__DIR__, 2) . '/templates/student/');

        add_action('init', array(self::class, 'registerRewriteRules'));
        add_action('init', array($this, 'ensureRewriteRules'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueueAssets'));
        add_filter('query_vars', array($router, 'queryVars'));
        add_filter('template_include', array($router, 'template'));
    }

    public function enqueueAssets(): void
    {
        if ('1' !== (string) get_query_var(DashboardRouter::QUERY_VAR)) {
            return;
        }

        $plugin_path = dirname(__DIR__, 2);
        $style_path = $plugin_path . '/assets/css/dashboard.css';

        wp_enqueue_style(
            'kaanbal-dashboard',
            plugins_url('assets/css/dashboard.css', $plugin_path . '/kaanbal.php'),
            array(),
            (string) filemtime($style_path)
        );
    }

    public static function registerRewriteRules(): void
    {
        add_rewrite_rule('^mis-cursos/?$', 'index.php?' . DashboardRouter::QUERY_VAR . '=1', 'top');
    }

    public function ensureRewriteRules(): void
    {
        if (self::REWRITE_VERSION === (string) get_option('kaanbal_dashboard_rewrite_version', '')) {
            return;
        }

        flush_rewrite_rules(false);
        update_option('kaanbal_dashboard_rewrite_version', self::REWRITE_VERSION, false);
    }

    private function query(): StudentDashboardQuery
    {
        $curriculum = new CurriculumService(new CurriculumRepository());

        return new StudentDashboardQuery(
            new EnrollmentRepository(),
            new CourseProgressService($curriculum, new LessonProgressRepository()),
            new QuizRepository(),
            new QuizAttemptRepository(),
            new QuizValidityService(new QuestionRepository()),
        );
    }
}
