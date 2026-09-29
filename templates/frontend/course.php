<?php

defined('ABSPATH') || exit;

$context    = \Kaanbal\Access\Presentation\Frontend\TemplateContext::all();
$curriculum = $context['curriculum'] ?? null;

if (! is_array($curriculum) || ! isset($curriculum['course'], $curriculum['modules'])) {
    return;
}

$course     = $curriculum['course'];
$duration   = get_post_meta($course->ID, '_kaanbal_duration', true);
$instructor = get_post_meta($course->ID, '_kaanbal_instructor_name', true);

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
            <?php foreach ($curriculum['modules'] as $module_item) : ?>
                <?php $module = $module_item['module']; ?>
                <section class="kaanbal-curriculum__module">
                    <h3><?php echo esc_html(get_the_title($module)); ?></h3>
                    <?php if (array() === $module_item['lessons']) : ?>
                        <p><?php esc_html_e('This module has no lessons yet.', 'kaanbal'); ?></p>
                    <?php else : ?>
                        <ul>
                            <?php foreach ($module_item['lessons'] as $lesson) : ?>
                                <li><a href="<?php echo esc_url(home_url('/courses/' . $course->post_name . '/lesson/' . $lesson->post_name . '/')); ?>"><span class="kaanbal-curriculum__lesson-title"><?php echo esc_html(get_the_title($lesson)); ?></span><span class="kaanbal-curriculum__play"><?php esc_html_e('Reproducir', 'kaanbal'); ?></span></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </section>
    </article>
</main>
<?php get_footer(); ?>
