<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Cancel;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class CancelPayment implements CommandInterface
{
    use AnnotatesCommand;
    use CancelMessageTrait;
}
