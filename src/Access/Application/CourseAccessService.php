<?php

declare(strict_types=1);

namespace Kaanbal\Access\Application;

use Kaanbal\Enrollment\Application\EnrollmentLookup;

final class CourseAccessService
{
    public function __construct(private readonly EnrollmentLookup $enrollments)
    {
    }

    public function check(int $user_id, int $course_id): CourseAccessResult
    {
        if ($user_id <= 0) {
            return CourseAccessResult::NotAuthenticated;
        }

        if ($course_id <= 0) {
            return CourseAccessResult::NotEnrolled;
        }

        $enrollment = $this->enrollments->findByUserAndCourse($user_id, $course_id);

        if (! is_array($enrollment)) {
            return CourseAccessResult::NotEnrolled;
        }

        return match ($enrollment['status']) {
            'active', 'completed' => CourseAccessResult::Granted,
            'revoked'             => CourseAccessResult::Revoked,
            default               => CourseAccessResult::NotEnrolled,
        };
    }

    public function canAccessCourse(int $user_id, int $course_id): bool
    {
        return $this->check($user_id, $course_id)->isGranted();
    }
}
