<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Make;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class MakePayment implements CommandInterface
{
    use AnnotatesCommand;
    use MakeMessageTrait;
}
