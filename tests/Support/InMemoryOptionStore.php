<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Support;

use Kaanbal\Shared\Database\OptionStore;

final class InMemoryOptionStore implements OptionStore
{
    /** @var array<string, mixed> */
    private array $values = array();

    public int $updates = 0;

    public function get(string $key, mixed $fallback = null): mixed
    {
        return $this->values[ $key ] ?? $fallback;
    }

    public function update(string $key, mixed $value): void
    {
        ++$this->updates;
        $this->values[ $key ] = $value;
    }
}
