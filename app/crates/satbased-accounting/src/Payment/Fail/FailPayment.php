<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Fail;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class FailPayment implements CommandInterface
{
    use AnnotatesCommand;
    use FailMessageTrait;
}
