<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Video;

final class YouTubeVideoProvider implements VideoProvider
{
    private const VIDEO_ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    public function key(): string
    {
        return 'youtube';
    }

    public function normalize(string $source): ?string
    {
        $source = trim($source);

        if (preg_match(self::VIDEO_ID_PATTERN, $source)) {
            return $source;
        }

        $url = wp_parse_url($source);

        if (! is_array($url) || empty($url['host'])) {
            return null;
        }

        $host = strtolower($url['host']);
        $path = trim($url['path'] ?? '', '/');
        $id   = null;

        if ('youtu.be' === $host || 'www.youtu.be' === $host) {
            $id = strtok($path, '/');
        }

        if (in_array($host, array('youtube.com', 'www.youtube.com', 'm.youtube.com'), true)) {
            if ('watch' === $path && isset($url['query'])) {
                parse_str($url['query'], $parameters);
                $id = $parameters['v'] ?? null;
            }

            if (str_starts_with($path, 'embed/')) {
                $id = substr($path, strlen('embed/'));
            }
        }

        return is_string($id) && preg_match(self::VIDEO_ID_PATTERN, $id) ? $id : null;
    }
}
