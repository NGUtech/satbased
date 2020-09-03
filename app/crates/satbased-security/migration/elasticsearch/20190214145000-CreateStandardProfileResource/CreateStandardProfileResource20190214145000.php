<?php declare(strict_types=1);

namespace Satbased\Security\Migration\Elasticsearch;

use Daikon\Elasticsearch7\Migration\Elasticsearch7Migration;

final class CreateStandardProfileResource20190214145000 extends Elasticsearch7Migration
{
    public function getDescription(string $direction = self::MIGRATE_UP): string
    {
        return $direction === self::MIGRATE_UP
            ? 'Create the Profile resource standard projection Elasticsearch index.'
            : 'Delete the Profile resource standard projection Elasticsearch index.';
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
        $this->putMapping($alias, $this->loadFile('profile-standard-mapping-20190214145000.json'));
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
        return $this->getIndexPrefix().'.profile.standard';
    }
}
