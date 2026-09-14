<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Infrastructure;

final class ContentTypes
{
    public const COURSE = 'kaanbal_course';
    public const MODULE = 'kaanbal_module';
    public const LESSON = 'kaanbal_lesson';

    public function register(): void
    {
        register_post_type(self::COURSE, $this->arguments(__('Courses', 'kaanbal'), __('Course', 'kaanbal')));
        register_post_type(self::MODULE, $this->arguments(__('Modules', 'kaanbal'), __('Module', 'kaanbal')));
        register_post_type(self::LESSON, $this->arguments(__('Lessons', 'kaanbal'), __('Lesson', 'kaanbal')));
    }

    /** @return array<string, mixed> */
    private function arguments(string $plural, string $singular): array
    {
        return array(
            'labels'             => array(
                'name'          => $plural,
                'singular_name' => $singular,
            ),
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => false,
            'supports'           => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'has_archive'        => false,
            'rewrite'            => false,
            'query_var'          => false,
            'exclude_from_search' => true,
        );
    }
}
