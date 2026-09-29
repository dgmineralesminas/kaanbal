<?php

declare(strict_types=1);

namespace Kaanbal\Progress\Application;

enum CompleteLessonResult: string
{
    case Completed = 'completed';
    case AlreadyCompleted = 'already_completed';
    case AccessDenied = 'access_denied';
    case InvalidLesson = 'invalid_lesson';
    case CourseMismatch = 'course_mismatch';
}
