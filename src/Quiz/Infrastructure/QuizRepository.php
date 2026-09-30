<?php

declare(strict_types=1);

namespace Kaanbal\Quiz\Infrastructure;

use Kaanbal\Courses\Infrastructure\ContentTypes;

final class QuizRepository
{
    public const COURSE_ID_META = '_kaanbal_course_id';
    public const PASSING_SCORE_META = '_kaanbal_passing_score';
    public const MAX_ATTEMPTS_META = '_kaanbal_max_attempts';
    public const REQUIRES_QUIZ_META = '_kaanbal_requires_final_quiz';
    public const CERTIFICATE_META = '_kaanbal_certificate_enabled';

    public function requiresQuiz(int $course_id): bool
    {
        return '1' === (string) get_post_meta($course_id, self::REQUIRES_QUIZ_META, true);
    }

    public function certificateEnabled(int $course_id): bool
    {
        return '1' === (string) get_post_meta($course_id, self::CERTIFICATE_META, true);
    }

    public function findForCourse(int $course_id): ?\WP_Post
    {
        $quizzes = get_posts(
            array(
                'post_type'      => ContentTypes::QUIZ,
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'meta_key'       => self::COURSE_ID_META,
                'meta_value'     => (string) $course_id,
                'orderby'        => 'ID',
                'order'          => 'ASC',
            )
        );

        return $quizzes[0] ?? null;
    }

    public function hasAnotherPublishedQuizForCourse(int $course_id, int $quiz_id): bool
    {
        $quizzes = get_posts(
            array(
                'post_type'      => ContentTypes::QUIZ,
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'post__not_in'   => array($quiz_id),
                'meta_key'       => self::COURSE_ID_META,
                'meta_value'     => (string) $course_id,
            )
        );

        return array() !== $quizzes;
    }

    public function belongsToCourse(int $quiz_id, int $course_id): bool
    {
        $quiz = get_post($quiz_id);

        return $quiz instanceof \WP_Post
            && ContentTypes::QUIZ === $quiz->post_type
            && 'publish' === $quiz->post_status
            && $course_id === (int) get_post_meta($quiz_id, self::COURSE_ID_META, true);
    }

    public function passingScore(int $quiz_id): int
    {
        return max(1, min(100, (int) get_post_meta($quiz_id, self::PASSING_SCORE_META, true) ?: 80));
    }

    public function maxAttempts(int $quiz_id): ?int
    {
        $value = get_post_meta($quiz_id, self::MAX_ATTEMPTS_META, true);

        return '' === $value || null === $value ? null : max(1, (int) $value);
    }

    /** @param list<int> $course_ids
     * @return array<int, array{required: bool, certificate_enabled: bool, quiz_id: int|null, max_attempts: int|null}>
     */
    public function dashboardDetailsForCourses(array $course_ids): array
    {
        $course_ids = array_values(array_unique(array_filter(array_map('absint', $course_ids))));

        if (array() === $course_ids) {
            return array();
        }

        update_meta_cache('post', $course_ids);
        $quizzes = get_posts(
            array(
                'post_type'      => ContentTypes::QUIZ,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => self::COURSE_ID_META,
                'meta_value'     => array_map(static fn (int $course_id): string => (string) $course_id, $course_ids),
                'meta_compare'   => 'IN',
                'orderby'        => array('ID' => 'ASC'),
            )
        );
        $quiz_by_course = array();

        foreach ($quizzes as $quiz) {
            $course_id = (int) get_post_meta($quiz->ID, self::COURSE_ID_META, true);

            if (! isset($quiz_by_course[$course_id])) {
                $quiz_by_course[$course_id] = $quiz;
            }
        }

        $details = array();

        foreach ($course_ids as $course_id) {
            $quiz = $quiz_by_course[$course_id] ?? null;
            $details[$course_id] = array(
                'required'            => $this->requiresQuiz($course_id),
                'certificate_enabled' => $this->certificateEnabled($course_id),
                'quiz_id'             => $quiz instanceof \WP_Post ? $quiz->ID : null,
                'max_attempts'        => $quiz instanceof \WP_Post ? $this->maxAttempts($quiz->ID) : null,
            );
        }

        return $details;
    }
}
