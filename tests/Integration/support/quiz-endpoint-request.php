<?php

declare(strict_types=1);

$wordpress_path = getenv('KAANBAL_WP_PATH');

if (! is_string($wordpress_path) || '' === $wordpress_path) {
    exit(2);
}

require_once rtrim($wordpress_path, DIRECTORY_SEPARATOR) . '/wp-load.php';
require_once dirname(__DIR__, 3) . '/kaanbal.php';

do_action('init');

$options = getopt('', array('user:', 'course:', 'quiz:', 'answers:', 'nonce:', 'posted-user:', 'score:', 'passed:'));
$user_id = isset($options['user']) ? absint($options['user']) : 0;
$course_id = isset($options['course']) ? absint($options['course']) : 0;
$quiz_id = isset($options['quiz']) ? absint($options['quiz']) : 0;
$nonce_mode = isset($options['nonce']) && is_string($options['nonce']) ? $options['nonce'] : 'missing';
$posted_user_id = isset($options['posted-user']) ? absint($options['posted-user']) : 0;
$submitted_answers = array();

if (isset($options['answers']) && is_string($options['answers'])) {
    foreach (explode(',', $options['answers']) as $pair) {
        $ids = array_map('absint', explode(':', $pair, 2));
        if (2 === count($ids) && $ids[0] > 0 && $ids[1] > 0) {
            $submitted_answers[$ids[0]] = $ids[1];
        }
    }
}

wp_set_current_user($user_id);

$_POST = array(
    'action'    => 'kaanbal_submit_final_quiz',
    'course_id' => $course_id,
    'quiz_id'   => $quiz_id,
    'answers'   => $submitted_answers,
);

if ($posted_user_id > 0) {
    $_POST['user_id'] = $posted_user_id;
}

if (isset($options['score'])) {
    $_POST['score'] = $options['score'];
}

if (isset($options['passed'])) {
    $_POST['passed'] = $options['passed'];
}

if ('valid' === $nonce_mode) {
    $_POST['_kaanbal_nonce'] = wp_create_nonce('kaanbal_submit_final_quiz_' . $quiz_id);
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

do_action($user_id > 0 ? 'admin_post_kaanbal_submit_final_quiz' : 'admin_post_nopriv_kaanbal_submit_final_quiz');

exit(5);
