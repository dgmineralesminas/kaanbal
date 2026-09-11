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

        $this->options->update(self::OPTION_NAME, $this->targetVersion);
    }
}
