<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Select;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class SelectPayment implements CommandInterface
{
    use AnnotatesCommand;
    use SelectMessageTrait;
}
