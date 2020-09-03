<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Login;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class LoginProfile implements CommandInterface
{
    use AnnotatesCommand;
    use LoginMessageTrait;
}
