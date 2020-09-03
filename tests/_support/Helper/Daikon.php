<?php

namespace Helper;

use Codeception\Exception\TestRuntimeException;
use Codeception\Module\Cli;

final class Daikon extends Cli
{
    public function runDaikon(string $command): void
    {
        $this->runShellCommand("bin/daikon $command");
    }

    public function setup(): void
    {
        $this->runDaikon('-vv migrate:down');
        $this->runDaikon('-vv migrate:up');
        $this->runDaikon('-vv fixture:import');
    }

    public function teardown(): void
    {
        $this->runDaikon('-vv migrate:down');
    }

    public function wait(int $timeout): void
    {
        if ($timeout >= 1000) {
            throw new TestRuntimeException(
                "
                Waiting for more then 1000 seconds: 16.6667 mins\n
                Please note that wait method accepts number of seconds as parameter."
            );
        }
        usleep($timeout * 1000000);
    }

    public function runWorker(string $job, string $queue, int $runtime = 5): void
    {
        pclose(popen("bin/daikon -d worker:run $job $queue", 'r'));
        $pid = trim(shell_exec("bin/exec php pgrep -f 'php bin/daikon.php worker:run $job $queue'"));
        $this->wait($runtime);
        exec("bin/exec php kill $pid");
    }
}
