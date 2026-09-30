<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Access\Application\VideoEmbedRenderer;
use PHPUnit\Framework\TestCase;

final class VideoEmbedRendererTest extends TestCase
{
    public function testItBuildsEscapedEmbedsForRegisteredProviders(): void
    {
        $renderer = new VideoEmbedRenderer();

        $youtube = $renderer->render('youtube', 'dQw4w9WgXcQ', 'A "lesson"');
        $vimeo = $renderer->render('vimeo', 'https://vimeo.com/123456789', 'A "lesson"');

        self::assertStringContainsString('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $youtube);
        self::assertStringContainsString('https://player.vimeo.com/video/123456789', $vimeo);
        self::assertStringContainsString('title="A &quot;lesson&quot;"', $youtube);
        self::assertStringContainsString('loading="lazy"', $vimeo);
        self::assertStringNotContainsString('<script', $youtube);
        self::assertStringNotContainsString('<script', $vimeo);
    }

    /** @dataProvider invalidVideoData */
    public function testItDoesNotRenderUntrustedOrUnsupportedVideoData(string $provider, string $source): void
    {
        self::assertSame('', (new VideoEmbedRenderer())->render($provider, $source, 'Lesson'));
    }

    /** @return array<string, array{string, string}> */
    public static function invalidVideoData(): array
    {
        return array(
            'unsupported provider' => array('unknown', '123456789'),
            'empty provider and source' => array('', ''),
            'youtube iframe payload' => array('youtube', '<iframe src="https://example.test"></iframe>'),
            'vimeo javascript payload' => array('vimeo', 'javascript:alert(1)'),
            'vimeo foreign host' => array('vimeo', 'https://example.test/123456789'),
        );
    }
}
