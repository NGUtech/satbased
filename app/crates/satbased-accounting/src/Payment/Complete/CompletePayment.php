<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Complete;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class CompletePayment implements CommandInterface
{
    use AnnotatesCommand;
    use CompleteMessageTrait;
}
