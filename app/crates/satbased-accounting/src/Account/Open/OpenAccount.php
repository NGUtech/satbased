<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Open;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class OpenAccount implements CommandInterface
{
    use AnnotatesCommand;
    use OpenMessageTrait;
}
