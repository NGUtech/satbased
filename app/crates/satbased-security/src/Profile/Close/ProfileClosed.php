<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Close;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class ProfileClosed implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use CloseMessageTrait;

    public static function fromCommand(CloseProfile $closeProfile): self
    {
        return self::fromNative($closeProfile->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return $otherEvent instanceof $this;
    }
}
