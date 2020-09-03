<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Settle;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentSettled implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use SettleMessageTrait;

    public static function fromCommand(SettlePayment $settlePayment): self
    {
        return self::fromNative($settlePayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        //@todo conflict on same paymentid
        return false;
    }
}
