<?php

declare(strict_types=1);

namespace Kaanbal\Shared\Database;

interface SchemaMigration
{
    public function install(): void;
}
