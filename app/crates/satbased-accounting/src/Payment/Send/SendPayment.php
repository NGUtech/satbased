<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Send;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class SendPayment implements CommandInterface
{
    use AnnotatesCommand;
    use SendMessageTrait;
}
