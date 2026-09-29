<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

use Kaanbal\Access\Presentation\Frontend\TemplateContext;

$context = TemplateContext::all();
$dashboard = $context['dashboard'] ?? array('courses' => array());
$courses = is_array($dashboard['courses'] ?? null) ? $dashboard['courses'] : array();
$quiz_messages = array(
    'locked'           => __('Completa las lecciones para acceder a tu evaluación final.', 'kaanbal'),
    'available'        => __('Tu evaluación final está disponible.', 'kaanbal'),
    'failed_can_retry' => __('No aprobaste el último intento. Puedes volver a intentarlo.', 'kaanbal'),
    'passed'           => __('Evaluación final aprobada.', 'kaanbal'),
    'no_attempts_left' => __('Intentos agotados.', 'kaanbal'),
    'unavailable'      => __('La evaluación final aún no está disponible.', 'kaanbal'),
);
?>
<main class="kaanbal-dashboard" aria-labelledby="kaanbal-dashboard-title">
    <header class="kaanbal-dashboard__header">
        <p class="kaanbal-dashboard__eyebrow"><?php esc_html_e('Mi aprendizaje', 'kaanbal'); ?></p>
        <h1 id="kaanbal-dashboard-title"><?php esc_html_e('Mis cursos', 'kaanbal'); ?></h1>
        <p><?php esc_html_e('Retoma tu progreso y consulta el estado de cada curso.', 'kaanbal'); ?></p>
    </header>

    <?php if (array() === $courses) : ?>
        <section class="kaanbal-dashboard__empty" aria-labelledby="kaanbal-empty-title">
            <span aria-hidden="true">✦</span>
            <h2 id="kaanbal-empty-title"><?php esc_html_e('Aún no tienes cursos disponibles.', 'kaanbal'); ?></h2>
            <p><?php esc_html_e('Cuando tengas acceso a un curso, aparecerá aquí.', 'kaanbal'); ?></p>
        </section>
    <?php else : ?>
        <section class="kaanbal-dashboard__courses" aria-label="<?php esc_attr_e('Tus cursos', 'kaanbal'); ?>">
            <?php foreach ($courses as $course) : ?>
                <?php
                $progress = $course['progress'];
                $quiz = $course['quiz'];
                $is_completed = 'completed' === $course['enrollment_status'];
                ?>
                <article class="kaanbal-dashboard-card<?php echo $is_completed ? ' is-completed' : ''; ?>">
                    <?php if (is_string($course['image_url']) && '' !== $course['image_url']) : ?>
                        <img class="kaanbal-dashboard-card__image" src="<?php echo esc_url($course['image_url']); ?>" alt="" loading="lazy" />
                    <?php else : ?>
                        <div class="kaanbal-dashboard-card__image kaanbal-dashboard-card__image--placeholder" aria-hidden="true">✦</div>
                    <?php endif; ?>

                    <div class="kaanbal-dashboard-card__body">
                        <div class="kaanbal-dashboard-card__topline">
                            <span class="kaanbal-dashboard-card__status"><?php echo esc_html($course['status_label']); ?></span>
                            <?php if ($is_completed && is_string($course['completed_at']) && '' !== $course['completed_at']) : ?>
                                <?php /* translators: %s: localized approval date. */ ?>
                                <time datetime="<?php echo esc_attr($course['completed_at']); ?>"><?php echo esc_html(sprintf(__('Aprobado el %s', 'kaanbal'), wp_date(get_option('date_format'), strtotime($course['completed_at'] . ' UTC')))); ?></time>
                            <?php endif; ?>
                        </div>

                        <h2><?php echo esc_html($course['title']); ?></h2>

                        <?php if ('' !== $course['instructor'] || '' !== $course['duration']) : ?>
                            <p class="kaanbal-dashboard-card__meta">
                                <?php if ('' !== $course['instructor']) : ?><span><?php echo esc_html($course['instructor']); ?></span><?php endif; ?>
                                <?php if ('' !== $course['duration']) : ?><span><?php echo esc_html($course['duration']); ?></span><?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <div class="kaanbal-dashboard-card__progress">
                            <div class="kaanbal-dashboard-card__progress-label">
                                <?php /* translators: 1: completed lessons, 2: total lessons. */ ?>
                                <span><?php echo esc_html(sprintf(__('%1$d de %2$d lecciones completadas', 'kaanbal'), $progress->completed_lessons, $progress->total_lessons)); ?></span>
                                <strong><?php echo esc_html((string) $progress->percentage); ?>%</strong>
                            </div>
                            <div class="kaanbal-dashboard-card__progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((string) $progress->percentage); ?>">
                                <span style="width: <?php echo esc_attr((string) $progress->percentage); ?>%"></span>
                            </div>
                        </div>

                        <?php if ($quiz['visible']) : ?>
                            <p class="kaanbal-dashboard-card__quiz" data-quiz-state="<?php echo esc_attr($quiz['state']); ?>"><?php echo esc_html($quiz_messages[$quiz['state']] ?? ''); ?></p>
                            <?php if ($quiz['show_attempts']) : ?>
                                <?php if ($quiz['attempts_limited']) : ?>
                                    <?php /* translators: %d: remaining quiz attempts. */ ?>
                                    <p class="kaanbal-dashboard-card__attempts"><?php echo esc_html(sprintf(_n('%d intento restante', '%d intentos restantes', (int) $quiz['attempts_remaining'], 'kaanbal'), (int) $quiz['attempts_remaining'])); ?></p>
                                <?php else : ?>
                                    <p class="kaanbal-dashboard-card__attempts"><?php esc_html_e('Intentos ilimitados.', 'kaanbal'); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($course['show_certificate']) : ?>
                            <p class="kaanbal-dashboard-card__certificate"><?php esc_html_e('Has aprobado este curso. En breve nos estaremos comunicando contigo para hacerte llegar tu certificado.', 'kaanbal'); ?></p>
                        <?php endif; ?>

                        <a class="kaanbal-dashboard-card__action" href="<?php echo esc_url($course['access_url']); ?>">
                            <?php echo esc_html($course['action_label']); ?><span aria-hidden="true">→</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
