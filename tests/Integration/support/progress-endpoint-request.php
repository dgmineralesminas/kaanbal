<?php

declare(strict_types=1);

$wordpress_path = getenv('KAANBAL_WP_PATH');

if (! is_string($wordpress_path) || '' === $wordpress_path) {
    exit(2);
}

require_once rtrim($wordpress_path, DIRECTORY_SEPARATOR) . '/wp-load.php';
require_once dirname(__DIR__, 3) . '/kaanbal.php';

do_action('init');

$options = getopt('', array('user:', 'course:', 'lesson:', 'nonce:'));
$user_id = isset($options['user']) ? absint($options['user']) : 0;
$course_id = isset($options['course']) ? absint($options['course']) : 0;
$lesson_id = isset($options['lesson']) ? absint($options['lesson']) : 0;
$nonce_mode = isset($options['nonce']) && is_string($options['nonce']) ? $options['nonce'] : 'missing';

wp_set_current_user($user_id);

$_POST = array(
    'action'    => 'kaanbal_complete_lesson',
    'course_id' => $course_id,
    'lesson_id' => $lesson_id,
);

if ('valid' === $nonce_mode) {
    $_POST['_kaanbal_nonce'] = wp_create_nonce('kaanbal_complete_lesson_' . $lesson_id);
} elseif ('invalid' === $nonce_mode) {
    $_POST['_kaanbal_nonce'] = 'invalid-nonce';
}

// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This fixture deliberately exercises valid, invalid, and missing nonce requests.
$_REQUEST = $_POST;
$_SERVER['REQUEST_METHOD'] = 'POST';

add_filter(
    'wp_die_handler',
    static function (): Closure {
        return static function (mixed $message, string $title = '', array|string|int $args = array()): void {
            $response = is_array($args) ? ($args['response'] ?? 500) : 500;
            exit(403 === $response ? 3 : 4);
        };
    },
);

do_action($user_id > 0 ? 'admin_post_kaanbal_complete_lesson' : 'admin_post_nopriv_kaanbal_complete_lesson');

exit(5);
