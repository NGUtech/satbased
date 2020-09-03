<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Debit;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class DebitAccount implements CommandInterface
{
    use AnnotatesCommand;
    use DebitMessageTrait;
}
