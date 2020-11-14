<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Register;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;

final class RegisterProfile implements CommandInterface
{
    use AnnotatesCommand;
    use RegisterMessageTrait;
}
