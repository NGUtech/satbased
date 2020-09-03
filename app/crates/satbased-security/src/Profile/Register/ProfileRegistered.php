<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Register;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class ProfileRegistered implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use RegisterMessageTrait;

    public static function fromCommand(RegisterProfile $registerProfile): self
    {
        return self::fromNative($registerProfile->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return $otherEvent instanceof $this;
    }
}
