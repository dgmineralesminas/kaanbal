<?php

declare(strict_types=1);

namespace Kaanbal\Bootstrap;

final class RequirementResult
{
    /** @param list<string> $errors */
    public function __construct(
        private readonly array $errors,
    ) {
    }

    public function isCompatible(): bool
    {
        return array() === $this->errors;
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
