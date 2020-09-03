<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Promote;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class PromoteProfile implements CommandInterface
{
    use AnnotatesCommand;
    use PromoteMessageTrait;
}
