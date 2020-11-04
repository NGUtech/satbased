<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Update;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class UpdatePayment implements CommandInterface
{
    use AnnotatesCommand;
    use UpdateMessageTrait;
}
