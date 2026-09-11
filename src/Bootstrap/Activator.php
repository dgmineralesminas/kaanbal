<?php

declare(strict_types=1);

namespace Kaanbal\Bootstrap;

use Kaanbal\Shared\Database\SchemaManager;
use Kaanbal\Shared\Database\WordPressOptionStore;

final class Activator
{
    public static function activate(): void
    {
        ( new SchemaManager(new WordPressOptionStore()) )->installOrUpgrade();
    }
}
