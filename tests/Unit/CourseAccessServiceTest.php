<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Access\Application\CourseAccessResult;
use Kaanbal\Access\Application\CourseAccessService;
use Kaanbal\Enrollment\Application\EnrollmentLookup;
use PHPUnit\Framework\TestCase;

final class CourseAccessServiceTest extends TestCase
{
    /** @dataProvider accessCases */
    public function testItCentralizesTheEnrollmentAccessPolicy(int $user_id, ?string $status, CourseAccessResult $expected): void
    {
        $lookup = new class ($status) implements EnrollmentLookup {
            public function __construct(private readonly ?string $status)
            {
            }

            public function findByUserAndCourse(int $user_id, int $course_id): ?array
            {
                return null === $this->status ? null : array('id' => 1, 'status' => $this->status);
            }
        };

        $service = new CourseAccessService($lookup);

        self::assertSame($expected, $service->check($user_id, 10));
        self::assertSame($expected->isGranted(), $service->canAccessCourse($user_id, 10));
    }

    /** @return array<string, array{int, string|null, CourseAccessResult}> */
    public static function accessCases(): array
    {
        return array(
            'active enrollment'    => array(12, 'active', CourseAccessResult::Granted),
            'completed enrollment' => array(12, 'completed', CourseAccessResult::Granted),
            'revoked enrollment'   => array(12, 'revoked', CourseAccessResult::Revoked),
            'missing enrollment'   => array(12, null, CourseAccessResult::NotEnrolled),
            'anonymous user'       => array(0, 'active', CourseAccessResult::NotAuthenticated),
        );
    }
}
