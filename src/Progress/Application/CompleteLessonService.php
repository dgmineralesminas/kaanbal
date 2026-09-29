<?php

declare(strict_types=1);

namespace Kaanbal\Progress\Application;

use Kaanbal\Access\Application\CourseAccessService;
use Kaanbal\Courses\Application\CurriculumService;
use Kaanbal\Courses\Infrastructure\ContentTypes;
use Kaanbal\Quiz\Application\CourseCompletionService;

final class CompleteLessonService
{
    public function __construct(
        private readonly CourseAccessService $access,
        private readonly CurriculumService $curriculum,
        private readonly LessonProgressStore $progress,
        private readonly ?CourseCompletionService $completion = null,
    ) {
    }

    public function complete(int $user_id, int $course_id, int $lesson_id): CompleteLessonResult
    {
        $lesson = get_post($lesson_id);

        if (! $lesson instanceof \WP_Post || ContentTypes::LESSON !== $lesson->post_type || 'publish' !== $lesson->post_status) {
            return CompleteLessonResult::InvalidLesson;
        }

        $hierarchy = $this->curriculum->hierarchyForPublishedLesson($lesson_id);

        if (! is_array($hierarchy) || $course_id !== $hierarchy['course']->ID) {
            return CompleteLessonResult::CourseMismatch;
        }

        if (! $this->access->canAccessCourse($user_id, $course_id)) {
            return CompleteLessonResult::AccessDenied;
        }

        $result = $this->progress->complete($user_id, $lesson_id)
            ? CompleteLessonResult::Completed
            : CompleteLessonResult::AlreadyCompleted;

        if (null !== $this->completion) {
            $this->completion->evaluate($user_id, $course_id);
        }

        return $result;
    }
}
