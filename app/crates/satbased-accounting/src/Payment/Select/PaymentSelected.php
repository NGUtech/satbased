<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Select;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentSelected implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use SelectMessageTrait;

    public static function fromCommand(SelectPayment $selectPayment): self
    {
        return self::fromNative($selectPayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
