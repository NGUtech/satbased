<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Close;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class CloseProfile implements CommandInterface
{
    use AnnotatesCommand;
    use CloseMessageTrait;
}
