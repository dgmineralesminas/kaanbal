<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Shared\Database\SchemaManager;
use Kaanbal\Shared\Database\SchemaMigration;
use Kaanbal\Tests\Support\InMemoryOptionStore;
use PHPUnit\Framework\TestCase;

final class SchemaManagerTest extends TestCase
{
    public function testItStoresTheSchemaVersionOnFirstInstall(): void
    {
        $options = new InMemoryOptionStore();
        $schema  = new SchemaManager($options, 1);

        $schema->installOrUpgrade();

        self::assertSame(1, $schema->installedVersion());
        self::assertSame(1, $options->updates);
    }

    public function testRepeatingInstallationDoesNotWriteAgain(): void
    {
        $options = new InMemoryOptionStore();
        $schema  = new SchemaManager($options, 1);

        $schema->installOrUpgrade();
        $schema->installOrUpgrade();

        self::assertSame(1, $options->updates);
    }

    public function testItRunsTheMigrationBeforeStoringTheVersion(): void
    {
        $options   = new InMemoryOptionStore();
        $migration = new class () implements SchemaMigration {
            public int $runs = 0;

            public function install(): void
            {
                ++$this->runs;
            }
        };
        $schema = new SchemaManager($options, 2, $migration);

        $schema->installOrUpgrade();
        $schema->installOrUpgrade();

        self::assertSame(1, $migration->runs);
        self::assertSame(2, $schema->installedVersion());
    }

    public function testAFailedMigrationDoesNotStoreTheVersion(): void
    {
        $options   = new InMemoryOptionStore();
        $migration = new class () implements SchemaMigration {
            public function install(): void
            {
                throw new \RuntimeException('Table could not be created.');
            }
        };
        $schema = new SchemaManager($options, 2, $migration);

        try {
            $schema->installOrUpgrade();
            self::fail('The migration failure was not reported.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Table could not be created.', $exception->getMessage());
        }

        self::assertSame(0, $schema->installedVersion());
        self::assertSame(0, $options->updates);
    }
}
