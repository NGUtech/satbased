<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Logout;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Satbased\Security\Profile\ProfileMessageTrait;

final class ProfileLoggedOut implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use ProfileMessageTrait;

    public static function fromCommand(LogoutProfile $logoutProfile): self
    {
        return self::fromNative($logoutProfile->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
