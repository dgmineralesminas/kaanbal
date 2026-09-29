<?php

defined('ABSPATH') || exit;

$context    = \Kaanbal\Access\Presentation\Frontend\TemplateContext::all();
$course     = $context['course'] ?? null;
$curriculum = $context['curriculum'] ?? null;
$module     = $context['module'] ?? null;
$lesson     = $context['lesson'] ?? null;
$navigation = $context['navigation'] ?? null;

if (! $course instanceof \WP_Post || ! is_array($curriculum) || ! $module instanceof \WP_Post || ! $lesson instanceof \WP_Post || ! is_array($navigation)) {
    return;
}

$provider = (string) get_post_meta($lesson->ID, '_kaanbal_video_provider', true);
$source   = (string) get_post_meta($lesson->ID, '_kaanbal_video_source', true);
$player   = (new \Kaanbal\Access\Application\YouTubeEmbedRenderer())->render($provider, $source, get_the_title($lesson));

get_header();
?>
<main class="kaanbal-lesson" aria-labelledby="kaanbal-lesson-title">
    <article class="kaanbal-lesson__shell">
        <header class="kaanbal-lesson__header">
            <p class="kaanbal-lesson__course"><a href="<?php echo esc_url(home_url('/courses/' . $course->post_name . '/')); ?>"><?php echo esc_html(get_the_title($course)); ?></a></p>
            <p class="kaanbal-lesson__module"><?php echo esc_html(get_the_title($module)); ?></p>
            <h1 id="kaanbal-lesson-title"><?php echo esc_html(get_the_title($lesson)); ?></h1>
        </header>
        <div class="kaanbal-lesson__learning-area">
            <div class="kaanbal-lesson__main-content">
                <?php if ('' !== $player) : ?>
                    <div class="kaanbal-player"><?php echo $player; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The renderer emits only escaped, internally constructed iframe markup. ?></div>
                <?php endif; ?>
                <div class="kaanbal-lesson__content">
                    <?php echo wp_kses_post(apply_filters('the_content', $lesson->post_content)); ?>
                </div>
                <nav class="kaanbal-lesson__navigation" aria-label="<?php esc_attr_e('Lesson navigation', 'kaanbal'); ?>">
                    <?php if ($navigation['previous'] instanceof \WP_Post) : ?>
                        <a href="<?php echo esc_url(home_url('/courses/' . $course->post_name . '/lesson/' . $navigation['previous']->post_name . '/')); ?>"><?php echo esc_html(get_the_title($navigation['previous'])); ?></a>
                    <?php endif; ?>
                    <?php if ($navigation['next'] instanceof \WP_Post) : ?>
                        <a href="<?php echo esc_url(home_url('/courses/' . $course->post_name . '/lesson/' . $navigation['next']->post_name . '/')); ?>"><?php echo esc_html(get_the_title($navigation['next'])); ?></a>
                    <?php endif; ?>
                </nav>
            </div>
            <aside class="kaanbal-lesson__curriculum" aria-labelledby="kaanbal-lesson-curriculum-title">
                <h2 id="kaanbal-lesson-curriculum-title"><?php esc_html_e('Contenido del curso', 'kaanbal'); ?></h2>
                <?php foreach ($curriculum['modules'] as $module_item) : ?>
                    <?php $curriculum_module = $module_item['module']; ?>
                    <section class="kaanbal-lesson__curriculum-module">
                        <h3><?php echo esc_html(get_the_title($curriculum_module)); ?></h3>
                        <ul>
                            <?php foreach ($module_item['lessons'] as $curriculum_lesson) : ?>
                                <?php $is_current_lesson = $curriculum_lesson->ID === $lesson->ID; ?>
                                <li class="<?php echo $is_current_lesson ? 'is-current' : ''; ?>">
                                    <a href="<?php echo esc_url(home_url('/courses/' . $course->post_name . '/lesson/' . $curriculum_lesson->post_name . '/')); ?>"<?php echo $is_current_lesson ? ' aria-current="page"' : ''; ?>><?php echo esc_html(get_the_title($curriculum_lesson)); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>
            </aside>
        </div>
    </article>
</main>
<?php get_footer(); ?>
