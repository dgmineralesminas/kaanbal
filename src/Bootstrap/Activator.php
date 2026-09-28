<?php

declare(strict_types=1);

namespace Kaanbal\Bootstrap;

use Kaanbal\Shared\Database\EnrollmentSchemaMigration;
use Kaanbal\Shared\Database\SchemaManager;
use Kaanbal\Shared\Database\WordPressOptionStore;

final class Activator
{
    public static function activate(): void
    {
        self::installSchema();
    }

    public static function installSchema(): void
    {
        global $wpdb;

        $migration = $wpdb instanceof \wpdb ? new EnrollmentSchemaMigration() : null;

        ( new SchemaManager(new WordPressOptionStore(), Version::DATABASE_SCHEMA, $migration) )->installOrUpgrade();
    }
}
