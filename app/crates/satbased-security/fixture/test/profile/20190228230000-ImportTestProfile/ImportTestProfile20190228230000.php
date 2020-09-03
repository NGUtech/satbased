<?php declare(strict_types=1);

namespace Satbased\Security\Fixture\Test\Profile;

use Daikon\Boot\Fixture\Fixture;
use Daikon\Metadata\Metadata;

final class ImportTestProfile20190228230000 extends Fixture
{
    protected function import(): void
    {
        foreach ($this->loadFile('test-profile-data.json') as $fixture) {
            $command = $fixture['@type']::fromNative($fixture['values']);
            $metadata = Metadata::fromNative($fixture['metadata'] ?? []);
            $this->publish($command, $metadata);
        }
    }

    private function loadFile(string $filename): array
    {
        return json_decode(file_get_contents(__DIR__."/$filename"), true);
    }
}
