<?php

defined('ABSPATH') || exit;

$context    = \Kaanbal\Access\Presentation\Frontend\TemplateContext::all();
$curriculum = $context['curriculum'] ?? null;
$progress   = $context['progress'] ?? null;
$quiz       = $context['quiz'] ?? null;

if (! is_array($curriculum) || ! isset($curriculum['course'], $curriculum['modules'])) {
    return;
}

$course     = $curriculum['course'];
$duration   = get_post_meta($course->ID, '_kaanbal_duration', true);
$instructor = get_post_meta($course->ID, '_kaanbal_instructor_name', true);
$completed_ids = $progress instanceof \Kaanbal\Progress\Application\CourseProgress ? $progress->completed_lesson_ids : array();

get_header();
?>
<main class="kaanbal-course" aria-labelledby="kaanbal-course-title">
    <article class="kaanbal-course__shell">
        <header class="kaanbal-course__hero">
            <div class="kaanbal-course__hero-copy">
                <p class="kaanbal-course__eyebrow"><?php esc_html_e('Tu curso', 'kaanbal'); ?></p>
                <h1 id="kaanbal-course-title"><?php echo esc_html(get_the_title($course)); ?></h1>
                <div class="kaanbal-course__meta">
                    <?php if ('' !== $instructor) : ?>
                        <p class="kaanbal-course__instructor"><?php echo esc_html($instructor); ?></p>
                    <?php endif; ?>
                    <?php if ('' !== $duration) : ?>
                        <p class="kaanbal-course__duration"><?php echo esc_html($duration); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (has_post_thumbnail($course)) : ?>
                <div class="kaanbal-course__image"><?php echo get_the_post_thumbnail($course, 'large'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress returns the generated image markup. ?></div>
            <?php endif; ?>
        </header>
        <div class="kaanbal-course__description">
            <?php echo wp_kses_post(apply_filters('the_content', $course->post_content)); ?>
        </div>
        <section class="kaanbal-curriculum" aria-labelledby="kaanbal-curriculum-title">
            <h2 id="kaanbal-curriculum-title"><?php esc_html_e('Comienza tu curso', 'kaanbal'); ?></h2>
            <?php if ($progress instanceof \Kaanbal\Progress\Application\CourseProgress) : ?>
                <div class="kaanbal-course__progress">
                    <p><?php echo esc_html(sprintf(__('%1$d de %2$d lecciones completadas · %3$d%%', 'kaanbal'), $progress->completed_lessons, $progress->total_lessons, $progress->percentage)); ?></p>
                    <div class="kaanbal-course__progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((string) $progress->percentage); ?>" aria-label="<?php esc_attr_e('Progreso del curso', 'kaanbal'); ?>">
                        <span style="width: <?php echo esc_attr((string) $progress->percentage); ?>%"></span>
                    </div>
                </div>
            <?php endif; ?>
            <?php foreach ($curriculum['modules'] as $module_item) : ?>
                <?php $module = $module_item['module']; ?>
                <section class="kaanbal-curriculum__module">
                    <h3><?php echo esc_html(get_the_title($module)); ?></h3>
                    <?php if (array() === $module_item['lessons']) : ?>
                        <p><?php esc_html_e('This module has no lessons yet.', 'kaanbal'); ?></p>
                    <?php else : ?>
                        <ul>
                            <?php foreach ($module_item['lessons'] as $lesson) : ?>
                                <?php $is_completed = in_array($lesson->ID, $completed_ids, true); ?>
                                <li class="<?php echo $is_completed ? 'is-completed' : ''; ?>"><a href="<?php echo esc_url(home_url('/courses/' . $course->post_name . '/lesson/' . $lesson->post_name . '/')); ?>"><span class="kaanbal-curriculum__lesson-title"><?php echo esc_html(get_the_title($lesson)); ?></span><span class="kaanbal-curriculum__play"><?php if ($is_completed) : ?><span aria-hidden="true">✓</span> <?php endif; ?><?php echo esc_html($is_completed ? __('Completada', 'kaanbal') : __('Reproducir', 'kaanbal')); ?></span></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </section>
        <?php if (is_array($quiz) && $progress instanceof \Kaanbal\Progress\Application\CourseProgress) : ?>
            <section class="kaanbal-course__assessment" aria-labelledby="kaanbal-assessment-title">
                <h2 id="kaanbal-assessment-title"><?php echo esc_html($quiz['requires_quiz'] ? __('Evaluación final', 'kaanbal') : __('Curso completado', 'kaanbal')); ?></h2>
                <?php if (! $quiz['requires_quiz'] && $progress->total_lessons > 0 && $progress->completed_lessons === $progress->total_lessons) : ?>
                    <div class="kaanbal-assessment__result is-passed"><span aria-hidden="true">✓</span><div><p><?php esc_html_e('Has completado y aprobado el curso.', 'kaanbal'); ?></p>
                    <?php if ($quiz['certificate_enabled']) : ?><p><?php esc_html_e('En breve nos estaremos comunicando contigo para hacerte llegar tu certificado.', 'kaanbal'); ?></p><?php endif; ?>
                    </div></div>
                <?php elseif ('already_passed' === $quiz['result']) : ?>
                    <div class="kaanbal-assessment__result is-passed"><span aria-hidden="true">✓</span><div><p><?php esc_html_e('Has completado y aprobado el curso.', 'kaanbal'); ?></p>
                    <?php if ($quiz['certificate_enabled']) : ?><p><?php esc_html_e('En breve nos estaremos comunicando contigo para hacerte llegar tu certificado.', 'kaanbal'); ?></p><?php endif; ?>
                    </div></div>
                <?php elseif ('eligible' === $quiz['result'] && $quiz['quiz'] instanceof \WP_Post) : ?>
                    <?php $assessment_dialog_id = 'kaanbal-assessment-' . $course->ID; ?>
                    <div class="kaanbal-assessment__card">
                        <span class="kaanbal-assessment__icon" aria-hidden="true">★</span>
                        <div class="kaanbal-assessment__card-copy">
                            <p class="kaanbal-assessment__eyebrow"><?php esc_html_e('Último paso', 'kaanbal'); ?></p>
                            <h3><?php esc_html_e('Demuestra lo que aprendiste', 'kaanbal'); ?></h3>
                            <p><?php esc_html_e('Terminaste todas las lecciones. Completa la evaluación para finalizar tu curso.', 'kaanbal'); ?></p>
                            <div class="kaanbal-assessment__facts"><span><?php echo esc_html(sprintf(_n('%d pregunta', '%d preguntas', count($quiz['questions']), 'kaanbal'), count($quiz['questions']))); ?></span><span><?php esc_html_e('Selección única', 'kaanbal'); ?></span></div>
                            <button class="kaanbal-assessment__start" type="button" data-kaanbal-assessment-open="<?php echo esc_attr($assessment_dialog_id); ?>" aria-haspopup="dialog" aria-controls="<?php echo esc_attr($assessment_dialog_id); ?>"><?php esc_html_e('Comenzar evaluación', 'kaanbal'); ?><span aria-hidden="true">→</span></button>
                        </div>
                    </div>
                    <dialog class="kaanbal-assessment__dialog" id="<?php echo esc_attr($assessment_dialog_id); ?>" aria-labelledby="<?php echo esc_attr($assessment_dialog_id . '-title'); ?>">
                        <div class="kaanbal-assessment__dialog-header">
                            <div><p><?php esc_html_e('Evaluación final', 'kaanbal'); ?></p><h3 id="<?php echo esc_attr($assessment_dialog_id . '-title'); ?>"><?php esc_html_e('Tómate tu tiempo', 'kaanbal'); ?></h3></div>
                            <button class="kaanbal-assessment__close" type="button" data-kaanbal-assessment-close aria-label="<?php esc_attr_e('Cerrar evaluación', 'kaanbal'); ?>">×</button>
                        </div>
                        <p class="kaanbal-assessment__dialog-intro"><?php esc_html_e('Elige una respuesta para cada pregunta y envía tu evaluación cuando termines.', 'kaanbal'); ?></p>
                        <form class="kaanbal-assessment__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="kaanbal_submit_final_quiz">
                        <input type="hidden" name="course_id" value="<?php echo esc_attr((string) $course->ID); ?>">
                        <input type="hidden" name="quiz_id" value="<?php echo esc_attr((string) $quiz['quiz']->ID); ?>">
                        <?php wp_nonce_field('kaanbal_submit_final_quiz_' . $quiz['quiz']->ID, '_kaanbal_nonce'); ?>
                        <?php foreach ($quiz['questions'] as $index => $question) : ?>
                            <fieldset class="kaanbal-assessment__question"><legend><span><?php echo esc_html(sprintf('%02d', $index + 1)); ?></span><?php echo esc_html($question['question_text']); ?></legend>
                                <?php foreach ($question['answers'] as $answer) : ?>
                                    <label class="kaanbal-assessment__option"><input type="radio" name="answers[<?php echo esc_attr((string) $question['id']); ?>]" value="<?php echo esc_attr((string) $answer['id']); ?>" required><span><?php echo esc_html($answer['answer_text']); ?></span></label>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endforeach; ?>
                        <button class="kaanbal-assessment__submit" type="submit"><?php esc_html_e('Enviar evaluación', 'kaanbal'); ?><span aria-hidden="true">→</span></button>
                        </form>
                    </dialog>
                <?php elseif ($quiz['requires_quiz']) : ?>
                    <div class="kaanbal-assessment__locked"><span aria-hidden="true">○</span><p><?php esc_html_e('Completa todas las lecciones para desbloquear tu evaluación final.', 'kaanbal'); ?></p></div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </article>
</main>
<?php get_footer(); ?>
