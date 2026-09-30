<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Infrastructure;

final class CurriculumRepository
{
    public const COURSE_ID_META = '_kaanbal_course_id';
    public const MODULE_ID_META = '_kaanbal_module_id';

    /** @return list<\WP_Post> */
    public function modulesForCourse(int $course_id, string $post_status = 'any'): array
    {
        return $this->findByRelationship(ContentTypes::MODULE, self::COURSE_ID_META, $course_id, $post_status);
    }

    /** @param list<int> $course_ids
     * @return list<\WP_Post>
     */
    public function modulesForCourses(array $course_ids, string $post_status = 'any'): array
    {
        $course_ids = array_values(array_unique(array_filter(array_map('absint', $course_ids))));

        if (array() === $course_ids) {
            return array();
        }

        return get_posts(
            array(
                'post_type'      => ContentTypes::MODULE,
                'post_status'    => $post_status,
                'posts_per_page' => -1,
                'meta_key'       => self::COURSE_ID_META,
                'meta_value'     => array_map(static fn (int $course_id): string => (string) $course_id, $course_ids),
                'meta_compare'   => 'IN',
                'orderby'        => array('menu_order' => 'ASC', 'ID' => 'ASC'),
            )
        );
    }

    /** @return list<\WP_Post> */
    public function lessonsForModule(int $module_id, string $post_status = 'any'): array
    {
        return $this->findByRelationship(ContentTypes::LESSON, self::MODULE_ID_META, $module_id, $post_status);
    }

    /** @param list<int> $module_ids
     * @return list<\WP_Post>
     */
    public function lessonsForModules(array $module_ids, string $post_status = 'any'): array
    {
        if (array() === $module_ids) {
            return array();
        }

        return get_posts(
            array(
                'post_type'      => ContentTypes::LESSON,
                'post_status'    => $post_status,
                'posts_per_page' => -1,
                'meta_key'       => self::MODULE_ID_META,
                'meta_value'     => array_map(static fn (int $module_id): string => (string) $module_id, $module_ids),
                'meta_compare'   => 'IN',
                'orderby'        => array('menu_order' => 'ASC', 'ID' => 'ASC'),
            )
        );
    }

    public function courseForModule(int $module_id, string $post_status = 'any'): ?\WP_Post
    {
        return $this->relatedPost($module_id, ContentTypes::MODULE, self::COURSE_ID_META, ContentTypes::COURSE, $post_status);
    }

    public function moduleForLesson(int $lesson_id, string $post_status = 'any'): ?\WP_Post
    {
        return $this->relatedPost($lesson_id, ContentTypes::LESSON, self::MODULE_ID_META, ContentTypes::MODULE, $post_status);
    }

    /** @return list<\WP_Post> */
    private function findByRelationship(string $post_type, string $meta_key, int $parent_id, string $post_status): array
    {
        if ($parent_id <= 0) {
            return array();
        }

        return get_posts(
            array(
                'post_type'      => $post_type,
                'post_status'    => $post_status,
                'posts_per_page' => -1,
                'meta_key'       => $meta_key,
                'meta_value'     => (string) $parent_id,
                'orderby'        => array('menu_order' => 'ASC', 'ID' => 'ASC'),
            )
        );
    }

    private function relatedPost(int $child_id, string $child_type, string $meta_key, string $parent_type, string $post_status): ?\WP_Post
    {
        $child = get_post($child_id);

        if (! $child instanceof \WP_Post || $child_type !== $child->post_type || ($post_status !== $child->post_status && 'any' !== $post_status)) {
            return null;
        }

        $parent_id = (int) get_post_meta($child_id, $meta_key, true);
        $parent    = get_post($parent_id);

        return $parent instanceof \WP_Post && $parent_type === $parent->post_type && ($post_status === $parent->post_status || 'any' === $post_status) ? $parent : null;
    }
}
