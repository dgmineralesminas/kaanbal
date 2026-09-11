<?php

declare(strict_types=1);

namespace Kaanbal\Shared\Database;

interface OptionStore
{
    public function get(string $key, mixed $fallback = null): mixed;

    public function update(string $key, mixed $value): void;
}
