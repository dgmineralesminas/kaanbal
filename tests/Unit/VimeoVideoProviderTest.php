<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Courses\Video\VimeoVideoProvider;
use PHPUnit\Framework\TestCase;

final class VimeoVideoProviderTest extends TestCase
{
    /** @dataProvider validSources */
    public function testItNormalizesSupportedSources(string $source): void
    {
        self::assertSame('123456789', (new VimeoVideoProvider())->normalize($source));
    }

    /** @return array<string, array{string}> */
    public static function validSources(): array
    {
        return array(
            'normalized id' => array('123456789'),
            'standard url' => array('https://vimeo.com/123456789'),
            'www url' => array('https://www.vimeo.com/123456789'),
            'trailing slash' => array('https://vimeo.com/123456789/'),
            'query string' => array('https://vimeo.com/123456789?share=copy'),
        );
    }

    /** @dataProvider invalidSources */
    public function testItRejectsInvalidSources(string $source): void
    {
        self::assertNull((new VimeoVideoProvider())->normalize($source));
    }

    /** @return array<string, array{string}> */
    public static function invalidSources(): array
    {
        return array(
            'empty' => array(''),
            'youtube url' => array('https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
            'foreign host with number' => array('https://example.com/123456789'),
            'non-numeric id' => array('https://vimeo.com/not-a-number'),
            'nested path' => array('https://vimeo.com/123456789/private'),
            'html payload' => array('<iframe src="https://player.vimeo.com/video/123456789"></iframe>'),
            'javascript url' => array('javascript:alert(1)'),
        );
    }

    public function testItBuildsItsEmbedUrlFromANormalizedId(): void
    {
        self::assertSame('https://player.vimeo.com/video/123456789', (new VimeoVideoProvider())->embedUrl('123456789'));
    }
}
