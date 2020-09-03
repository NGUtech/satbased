<?php declare(strict_types=1);

namespace Satbased\Accounting\Migration\Elasticsearch;

use Daikon\Elasticsearch7\Migration\Elasticsearch7Migration;

final class CreateStandardAccountResource20190302120000 extends Elasticsearch7Migration
{
    public function getDescription(string $direction = self::MIGRATE_UP): string
    {
        return $direction === self::MIGRATE_UP
            ? 'Create the Account resource standard projection Elasticsearch index.'
            : 'Delete the Account resource standard projection Elasticsearch index.';
    }

    public function isReversible(): bool
    {
        return true;
    }

    protected function up(): void
    {
        $alias = $this->getAlias();
        $index = sprintf('%s.%d', $alias, $this->getVersion());
        $this->createIndex($index, $this->loadFile('index-settings.json'));
        $this->createAlias($index, $alias);
        $this->putMapping($alias, $this->loadFile('account-standard-mapping-20190302120000.json'));
    }

    protected function down(): void
    {
        $index = current($this->getIndicesWithAlias($this->getAlias()));
        $this->deleteIndex($index);
    }

    private function loadFile(string $filename): array
    {
        return json_decode(file_get_contents(__DIR__."/$filename"), true);
    }

    private function getAlias(): string
    {
        return $this->getIndexPrefix().'.account.standard';
    }
}
