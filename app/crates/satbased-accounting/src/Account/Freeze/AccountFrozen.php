<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Freeze;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;

use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class AccountFrozen implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use FreezeMessageTrait;

    public static function fromCommand(FreezeAccount $freezeAccount): self
    {
        return self::fromNative($freezeAccount->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
