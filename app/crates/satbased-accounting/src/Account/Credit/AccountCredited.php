<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Credit;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class AccountCredited implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use CreditMessageTrait;

    public static function fromCommand(CreditAccount $creditAccount): self
    {
        return self::fromNative($creditAccount->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        //@todo conflict on same paymentid
        return false;
    }
}
