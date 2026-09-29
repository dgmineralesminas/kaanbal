<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Access\Application\LessonNavigationService;
use PHPUnit\Framework\TestCase;

final class LessonNavigationServiceTest extends TestCase
{
    public function testItSkipsEmptyModulesAndResolvesPreviousAndNextLessons(): void
    {
        $first  = (object) array('ID' => 10);
        $second = (object) array('ID' => 20);
        $third  = (object) array('ID' => 30);
        $module = static fn (array $lessons): array => array('module' => (object) array('ID' => 1), 'lessons' => $lessons);
        $course = array(
            'course'  => (object) array('ID' => 100),
            'modules' => array($module(array($first)), $module(array()), $module(array($second, $third))),
        );
        $service = new LessonNavigationService();

        self::assertSame(array('previous' => null, 'next' => $second), $service->forLesson(10, $course));
        self::assertSame(array('previous' => $first, 'next' => $third), $service->forLesson(20, $course));
        self::assertSame(array('previous' => $second, 'next' => null), $service->forLesson(30, $course));
    }

    public function testItReturnsNoNeighborsForAnUnknownLesson(): void
    {
        $curriculum = array(
            'course'  => (object) array('ID' => 100),
            'modules' => array(),
        );

        self::assertSame(
            array('previous' => null, 'next' => null),
            (new LessonNavigationService())->forLesson(99, $curriculum)
        );
    }
}
