<?php

defined('ABSPATH') || exit;

get_header();
?>
<main class="kaanbal-not-found">
    <h1><?php esc_html_e('Content not found', 'kaanbal'); ?></h1>
    <p><?php esc_html_e('The requested course or lesson is not available.', 'kaanbal'); ?></p>
</main>
<?php get_footer(); ?>
