<?php declare(strict_types=1);

namespace Satbased\Accounting\Migration\Elasticsearch;

use Daikon\Elasticsearch7\Migration\Elasticsearch7Migration;

final class InitializeProjectionStore20190210134000 extends Elasticsearch7Migration
{
    public function getDescription(string $direction = self::MIGRATE_UP): string
    {
        return $direction === self::MIGRATE_UP
            ? 'Create the Elasticsearch migration list for the Satbased-Accounting context.'
            : 'Delete the Elasticsearch migration list for the Satbased-Accounting context.';
    }

    public function isReversible(): bool
    {
        return true;
    }

    protected function up(): void
    {
        $index = $this->getIndexPrefix().'.migration_list';
        $this->createIndex($index, $this->loadFile('index-settings.json'));
        $this->putMapping($index, $this->loadFile('migration_list-mapping-20190210134000.json'));
    }

    protected function down(): void
    {
        $index = $this->getIndexPrefix().'.migration_list';
        $this->deleteIndex($index);
    }

    private function loadFile(string $filename): array
    {
        return json_decode(file_get_contents(__DIR__.'/'.$filename), true);
    }
}
