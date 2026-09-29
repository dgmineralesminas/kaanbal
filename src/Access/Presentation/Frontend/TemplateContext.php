<?php

declare(strict_types=1);

namespace Kaanbal\Access\Presentation\Frontend;

final class TemplateContext
{
    /** @var array<string, mixed> */
    private static array $values = array();

    /** @param array<string, mixed> $values */
    public static function replace(array $values): void
    {
        self::$values = $values;
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return self::$values;
    }
}
