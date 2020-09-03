<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Credit;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class CreditAccount implements CommandInterface
{
    use AnnotatesCommand;
    use CreditMessageTrait;
}
