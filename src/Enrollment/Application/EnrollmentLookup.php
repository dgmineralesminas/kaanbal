<?php

declare(strict_types=1);

namespace Kaanbal\Enrollment\Application;

interface EnrollmentLookup
{
    /** @return array{id: int, status: string}|null */
    public function findByUserAndCourse(int $user_id, int $course_id): ?array;
}
