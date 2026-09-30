<?php

declare(strict_types=1);

namespace Kaanbal\Access\Application;

use Kaanbal\Courses\Video\VideoProviders;

final class VideoEmbedRenderer
{
    public function __construct(private readonly VideoProviders $providers = new VideoProviders())
    {
    }

    public function render(string $provider_key, string $source, string $title): string
    {
        $provider = $this->providers->forKey($provider_key);
        $video_id = $provider?->normalize($source);

        if (! is_string($video_id)) {
            return '';
        }

        return sprintf(
            '<iframe class="kaanbal-player__video" src="%1$s" title="%2$s" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
            esc_url($provider->embedUrl($video_id)),
            esc_attr($title)
        );
    }
}
