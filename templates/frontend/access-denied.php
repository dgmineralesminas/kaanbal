<?php

defined('ABSPATH') || exit;

get_header();
?>
<main class="kaanbal-access-denied">
    <h1><?php esc_html_e('Course access required', 'kaanbal'); ?></h1>
    <p><?php esc_html_e('You do not have access to this course.', 'kaanbal'); ?></p>
</main>
<?php get_footer(); ?>
