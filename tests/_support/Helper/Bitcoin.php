<?php

namespace Helper;

use Codeception\Module\Cli;
use Codeception\Util\JsonArray;

final class Bitcoin extends Cli
{
    public function bootstrap(): void
    {
        $this->runShellCommand('bin/bootstrap');
    }

    public function runStack(string $node, string $command): void
    {
        $this->runShellCommand(sprintf('bin/stack %s %s', $node, $command));
    }

    public function runStackAndDetach(string $node, string $command): void
    {
        $this->runShellCommand(sprintf('bin/stack -d %s %s', $node, $command));
    }

    public function seeInStackOutput($text): void
    {
        $this->seeInShellOutput($text);
    }

    public function grabDataFromOutputByJsonPath($jsonPath): array
    {
        return (new JsonArray($this->output))->filterByJsonPath($jsonPath);
    }

    public function getOutputOfStackCommand(): string
    {
        return $this->output;
    }
}
