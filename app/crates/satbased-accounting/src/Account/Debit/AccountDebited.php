<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Debit;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class AccountDebited implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use DebitMessageTrait;

    public static function fromCommand(DebitAccount $debitAccount): self
    {
        return self::fromNative($debitAccount->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        //@todo conflict on same paymentid
        return false;
    }
}
