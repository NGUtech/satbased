<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Receive;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class ReceivePayment implements CommandInterface
{
    use AnnotatesCommand;
    use ReceiveMessageTrait;
}
