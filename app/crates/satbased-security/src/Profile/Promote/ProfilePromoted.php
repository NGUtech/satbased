<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Promote;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class ProfilePromoted implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use PromoteMessageTrait;

    public static function fromCommand(PromoteProfile $promoteProfile): self
    {
        return self::fromNative($promoteProfile->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
