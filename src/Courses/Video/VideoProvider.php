<?php

declare(strict_types=1);

namespace Kaanbal\Courses\Video;

interface VideoProvider
{
    public function key(): string;

    public function normalize(string $source): ?string;

    public function embedUrl(string $video_id): string;
}
