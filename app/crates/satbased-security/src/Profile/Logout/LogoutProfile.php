<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Logout;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Satbased\Security\Profile\ProfileMessageTrait;

final class LogoutProfile implements CommandInterface
{
    use AnnotatesCommand;
    use ProfileMessageTrait;
}
