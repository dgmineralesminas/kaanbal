<?php

declare(strict_types=1);

namespace Kaanbal\Shared\Database;

use Kaanbal\Bootstrap\Version;

final class SchemaManager
{
    private const OPTION_NAME = 'kaanbal_db_version';

    public function __construct(
        private readonly OptionStore $options,
        private readonly int $targetVersion = Version::DATABASE_SCHEMA,
        private readonly ?SchemaMigration $migration = null,
    ) {
    }

    public function installedVersion(): int
    {
        return (int) $this->options->get(self::OPTION_NAME, 0);
    }

    public function installOrUpgrade(): void
    {
        $installedVersion = $this->installedVersion();

        if ($installedVersion >= $this->targetVersion) {
            return;
        }

        // The version is stored only after the migration succeeds, so a failed
        // migration is retried on the next request instead of being skipped forever.
        $this->migration?->install();

        $this->options->update(self::OPTION_NAME, $this->targetVersion);
    }
}
