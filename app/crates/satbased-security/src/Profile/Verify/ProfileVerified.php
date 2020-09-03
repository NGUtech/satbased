<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Verify;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class ProfileVerified implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use VerifyMessageTrait;

    public static function fromCommand(VerifyProfile $verifyProfile): self
    {
        return self::fromNative($verifyProfile->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return $otherEvent instanceof $this;
    }
}
