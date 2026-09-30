<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Video;

final class VideoProviders
{
    public function forKey(string $key): ?VideoProvider
    {
        foreach (array(new YouTubeVideoProvider(), new VimeoVideoProvider()) as $provider) {
            if ($provider->key() === $key) {
                return $provider;
            }
        }

        return null;
    }

    public function supports(string $key): bool
    {
        return $this->forKey($key) instanceof VideoProvider;
    }
}
