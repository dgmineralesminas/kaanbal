<?php

declare(strict_types=1);

namespace Kaanbal\Courses;

use Kaanbal\Bootstrap\BootableService;
use Kaanbal\Courses\Infrastructure\ContentTypes;
use Kaanbal\Courses\Presentation\Admin\CurriculumMetaBoxes;

final class CoursesModule implements BootableService
{
    public function register(): void
    {
        $content_types = new ContentTypes();
        $meta_boxes    = new CurriculumMetaBoxes();

        add_action('init', array($content_types, 'register'));
        add_action('add_meta_boxes', array($meta_boxes, 'register'));
        add_action('save_post_kaanbal_course', array($meta_boxes, 'saveCourse'));
        add_action('save_post_kaanbal_module', array($meta_boxes, 'saveModule'));
        add_action('save_post_kaanbal_lesson', array($meta_boxes, 'saveLesson'));
    }
}
