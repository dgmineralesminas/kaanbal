<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Courses\Video\VideoProviders;
use Kaanbal\Courses\Video\VimeoVideoProvider;
use Kaanbal\Courses\Video\YouTubeVideoProvider;
use PHPUnit\Framework\TestCase;

final class VideoProvidersTest extends TestCase
{
    public function testItResolvesRegisteredProviders(): void
    {
        $providers = new VideoProviders();

        self::assertInstanceOf(YouTubeVideoProvider::class, $providers->forKey('youtube'));
        self::assertInstanceOf(VimeoVideoProvider::class, $providers->forKey('vimeo'));
        self::assertTrue($providers->supports('youtube'));
        self::assertTrue($providers->supports('vimeo'));
    }

    public function testItRejectsUnknownProviders(): void
    {
        $providers = new VideoProviders();

        self::assertNull($providers->forKey('unknown'));
        self::assertFalse($providers->supports('unknown'));
    }
}
