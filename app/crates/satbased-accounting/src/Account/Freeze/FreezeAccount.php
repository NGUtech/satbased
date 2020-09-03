<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Freeze;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class FreezeAccount implements CommandInterface
{
    use AnnotatesCommand;
    use FreezeMessageTrait;
}
