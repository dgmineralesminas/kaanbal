<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Access\Application\YouTubeEmbedRenderer;
use PHPUnit\Framework\TestCase;

final class YouTubeEmbedRendererTest extends TestCase
{
    public function testItBuildsAnEscapedNoCookieEmbedFromANormalizedVideoId(): void
    {
        $embed = (new YouTubeEmbedRenderer())->render('youtube', 'dQw4w9WgXcQ', 'A "lesson"');

        self::assertStringContainsString('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $embed);
        self::assertStringContainsString('modestbranding=1', $embed);
        self::assertStringContainsString('title="A &quot;lesson&quot;"', $embed);
        self::assertStringNotContainsString('web-share', $embed);
        self::assertStringNotContainsString('clipboard-write', $embed);
        self::assertStringNotContainsString('<script', $embed);
    }

    /** @dataProvider invalidVideoData */
    public function testItDoesNotRenderUntrustedOrUnsupportedVideoData(string $provider, string $source): void
    {
        self::assertSame('', (new YouTubeEmbedRenderer())->render($provider, $source, 'Lesson'));
    }

    /** @return array<string, array{string, string}> */
    public static function invalidVideoData(): array
    {
        return array(
            'unsupported provider' => array('vimeo', '12345678901'),
            'empty provider and source' => array('', ''),
            'arbitrary iframe'     => array('youtube', '<iframe src="https://example.test"></iframe>'),
            'invalid source'       => array('youtube', 'not-a-video-id'),
        );
    }
}
