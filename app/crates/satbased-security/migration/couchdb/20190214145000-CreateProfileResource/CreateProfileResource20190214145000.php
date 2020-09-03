<?php declare(strict_types=1);

namespace Satbased\Security\Migration\CouchDb;

use Daikon\CouchDb\Migration\CouchDbMigration;

final class CreateProfileResource20190214145000 extends CouchDbMigration
{
    public function getDescription(string $direction = self::MIGRATE_UP): string
    {
        return $direction === self::MIGRATE_UP
            ? 'Create CouchDb default views for the Profile resource.'
            : 'Delete CouchDb default views for the Profile resource.';
    }

    public function isReversible(): bool
    {
        return true;
    }

    protected function up(): void
    {
        $this->createDesignDoc(
            $this->getDatabaseName(),
            'satbased-security-profile',
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
        $this->deleteDesignDoc($this->getDatabaseName(), 'satbased-security-profile');
    }

    private function loadFile(string $filename): string
    {
        return file_get_contents(__DIR__."/$filename");
    }
}
