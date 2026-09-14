<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Courses\Video\YouTubeVideoProvider;
use PHPUnit\Framework\TestCase;

final class YouTubeVideoProviderTest extends TestCase
{
    /** @dataProvider validSources */
    public function testItNormalizesSupportedSources(string $source): void
    {
        self::assertSame('dQw4w9WgXcQ', (new YouTubeVideoProvider())->normalize($source));
    }

    /** @return array<string, array{string}> */
    public static function validSources(): array
    {
        return array(
            'video id'      => array('dQw4w9WgXcQ'),
            'watch url'     => array('https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
            'short url'     => array('https://youtu.be/dQw4w9WgXcQ'),
            'embed url'     => array('https://www.youtube.com/embed/dQw4w9WgXcQ'),
        );
    }

    /** @dataProvider invalidSources */
    public function testItRejectsInvalidSources(string $source): void
    {
        self::assertNull((new YouTubeVideoProvider())->normalize($source));
    }

    /** @return array<string, array{string}> */
    public static function invalidSources(): array
    {
        return array(
            'empty'          => array(''),
            'wrong host'     => array('https://example.com/watch?v=dQw4w9WgXcQ'),
            'missing id'     => array('https://www.youtube.com/watch'),
            'invalid id size' => array('short'),
        );
    }
}
