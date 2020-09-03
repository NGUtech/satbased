<?php declare(strict_types=1);

namespace Satbased\Security\Migration\CouchDb;

use Daikon\CouchDb\Migration\CouchDbMigration;

final class InitializeEventStore20190210134000 extends CouchDbMigration
{
    public function getDescription(string $direction = self::MIGRATE_UP): string
    {
        return $direction === self::MIGRATE_UP
            ? 'Create the CouchDb database for the Satbased-Security context.'
            : 'Delete the CouchDb database for the Satbased-Security context.';
    }

    public function isReversible(): bool
    {
        return true;
    }

    protected function up(): void
    {
        $this->createDatabase($this->getDatabaseName());
    }

    protected function down(): void
    {
        $this->deleteDatabase($this->getDatabaseName());
    }
}
