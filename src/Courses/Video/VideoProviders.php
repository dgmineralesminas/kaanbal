<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Video;

final class VideoProviders
{
    public function forKey(string $key): ?VideoProvider
    {
        $provider = new YouTubeVideoProvider();

        return $provider->key() === $key ? $provider : null;
    }
}
