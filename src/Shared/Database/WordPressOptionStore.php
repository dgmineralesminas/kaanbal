<?php

declare(strict_types=1);

namespace Kaanbal\Shared\Database;

final class WordPressOptionStore implements OptionStore
{
    public function get(string $key, mixed $fallback = null): mixed
    {
        return get_option($key, $fallback);
    }

    public function update(string $key, mixed $value): void
    {
        update_option($key, $value, false);
    }
}
