<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Video;

final class VimeoVideoProvider implements VideoProvider
{
    private const VIDEO_ID_PATTERN = '/^[0-9]+$/';

    public function key(): string
    {
        return 'vimeo';
    }

    public function normalize(string $source): ?string
    {
        $source = trim($source);

        if (preg_match(self::VIDEO_ID_PATTERN, $source)) {
            return $source;
        }

        $url = wp_parse_url($source);

        if (! is_array($url) || empty($url['host']) || ! isset($url['scheme']) || ! in_array(strtolower((string) $url['scheme']), array('http', 'https'), true)) {
            return null;
        }

        if (! in_array(strtolower((string) $url['host']), array('vimeo.com', 'www.vimeo.com'), true)) {
            return null;
        }

        $path = trim((string) ($url['path'] ?? ''), '/');

        if ('' === $path || str_contains($path, '/')) {
            return null;
        }

        return preg_match(self::VIDEO_ID_PATTERN, $path) ? $path : null;
    }

    public function embedUrl(string $video_id): string
    {
        return 'https://player.vimeo.com/video/' . rawurlencode($video_id);
    }
}
