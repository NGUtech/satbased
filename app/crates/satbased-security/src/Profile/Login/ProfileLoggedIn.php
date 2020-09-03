<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Login;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class ProfileLoggedIn implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use LoginMessageTrait;

    public static function fromCommand(LoginProfile $loginProfile): self
    {
        return self::fromNative($loginProfile->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
