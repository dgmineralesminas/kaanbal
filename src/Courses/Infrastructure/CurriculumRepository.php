<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Infrastructure;

final class CurriculumRepository
{
    public const COURSE_ID_META = '_kaanbal_course_id';
    public const MODULE_ID_META = '_kaanbal_module_id';

    /** @return list<\WP_Post> */
    public function modulesForCourse(int $course_id): array
    {
        return $this->findByRelationship(ContentTypes::MODULE, self::COURSE_ID_META, $course_id);
    }

    /** @return list<\WP_Post> */
    public function lessonsForModule(int $module_id): array
    {
        return $this->findByRelationship(ContentTypes::LESSON, self::MODULE_ID_META, $module_id);
    }

    /** @param list<int> $module_ids
     * @return list<\WP_Post>
     */
    public function lessonsForModules(array $module_ids): array
    {
        if (array() === $module_ids) {
            return array();
        }

        return get_posts(
            array(
                'post_type'      => ContentTypes::LESSON,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'meta_key'       => self::MODULE_ID_META,
                'meta_value'     => array_map(static fn (int $module_id): string => (string) $module_id, $module_ids),
                'meta_compare'   => 'IN',
                'orderby'        => array('menu_order' => 'ASC', 'ID' => 'ASC'),
            )
        );
    }

    public function courseForModule(int $module_id): ?\WP_Post
    {
        return $this->relatedPost($module_id, ContentTypes::MODULE, self::COURSE_ID_META, ContentTypes::COURSE);
    }

    public function moduleForLesson(int $lesson_id): ?\WP_Post
    {
        return $this->relatedPost($lesson_id, ContentTypes::LESSON, self::MODULE_ID_META, ContentTypes::MODULE);
    }

    /** @return list<\WP_Post> */
    private function findByRelationship(string $post_type, string $meta_key, int $parent_id): array
    {
        if ($parent_id <= 0) {
            return array();
        }

        return get_posts(
            array(
                'post_type'      => $post_type,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'meta_key'       => $meta_key,
                'meta_value'     => (string) $parent_id,
                'orderby'        => array('menu_order' => 'ASC', 'ID' => 'ASC'),
            )
        );
    }

    private function relatedPost(int $child_id, string $child_type, string $meta_key, string $parent_type): ?\WP_Post
    {
        $child = get_post($child_id);

        if (! $child instanceof \WP_Post || $child_type !== $child->post_type) {
            return null;
        }

        $parent_id = (int) get_post_meta($child_id, $meta_key, true);
        $parent    = get_post($parent_id);

        return $parent instanceof \WP_Post && $parent_type === $parent->post_type ? $parent : null;
    }
}
