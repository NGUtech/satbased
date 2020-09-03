<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Request;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class RequestPayment implements CommandInterface
{
    use AnnotatesCommand;
    use RequestMessageTrait;
}
