<?php

declare(strict_types=1);

namespace Kaanbal\Tests\Unit;

use Kaanbal\Shared\Database\SchemaManager;
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
}
