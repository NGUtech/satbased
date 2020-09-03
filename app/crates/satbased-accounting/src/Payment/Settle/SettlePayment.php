<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Settle;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class SettlePayment implements CommandInterface
{
    use AnnotatesCommand;
    use SettleMessageTrait;
}
