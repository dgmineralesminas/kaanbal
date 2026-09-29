<?php

declare(strict_types=1);

namespace Kaanbal\Access\Application;

use Kaanbal\Courses\Video\YouTubeVideoProvider;

final class YouTubeEmbedRenderer
{
    public function render(string $provider, string $source, string $title): string
    {
        if ('youtube' !== $provider) {
            return '';
        }

        $video_id = (new YouTubeVideoProvider())->normalize($source);

        if (! is_string($video_id)) {
            return '';
        }

        $url = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($video_id) . '?modestbranding=1&rel=0&iv_load_policy=3&playsinline=1';

        return sprintf(
            '<iframe class="kaanbal-player__video" src="%1$s" title="%2$s" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
            esc_url($url),
            esc_attr($title)
        );
    }
}
