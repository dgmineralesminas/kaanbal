<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

$context = \Kaanbal\Access\Presentation\Frontend\TemplateContext::all();
$login_url = is_string($context['login_url'] ?? null) ? $context['login_url'] : wp_login_url();

get_header();
?>
<main class="kaanbal-dashboard kaanbal-dashboard--denied">
    <section class="kaanbal-dashboard__empty">
        <span aria-hidden="true">!</span>
        <h1><?php esc_html_e('Inicia sesión para ver tus cursos.', 'kaanbal'); ?></h1>
        <p><?php esc_html_e('Esta información solo está disponible para alumnos autenticados.', 'kaanbal'); ?></p>
        <a class="kaanbal-dashboard-card__action" href="<?php echo esc_url($login_url); ?>">
            <?php esc_html_e('Iniciar sesión', 'kaanbal'); ?><span aria-hidden="true">→</span>
        </a>
    </section>
</main>
<?php get_footer(); ?>
