<?php declare(strict_types=1);

namespace Satbased\Accounting\Migration\CouchDb;

use Daikon\CouchDb\Migration\CouchDbMigration;

final class CreateAccountResource20190302120000 extends CouchDbMigration
{
    public function getDescription(string $direction = self::MIGRATE_UP): string
    {
        return $direction === self::MIGRATE_UP
            ? 'Create CouchDb default views for the Account resource.'
            : 'Delete CouchDb default views for the Account resource.';
    }

    public function isReversible(): bool
    {
        return true;
    }

    protected function up(): void
    {
        $this->createDesignDoc(
            $this->getDatabaseName(),
            'satbased-accounting-account',
            [
                'commit_stream' => [
                    'map' => $this->loadFile('commit_stream.map.js'),
                    'reduce' => $this->loadFile('commit_stream.reduce.js')
                ],
                'commits_by_timestamp' => [
                    'map' => $this->loadFile('commits_by_timestamp.map.js')
                ]
            ]
        );
    }

    protected function down(): void
    {
        $this->deleteDesignDoc($this->getDatabaseName(), 'satbased-accounting-account');
    }

    private function loadFile(string $filename): string
    {
        return file_get_contents(__DIR__."/$filename");
    }
}
