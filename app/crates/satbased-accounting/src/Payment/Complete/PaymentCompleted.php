<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Complete;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentCompleted implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use CompleteMessageTrait;

    public static function fromCommand(CompletePayment $completePayment): self
    {
        return self::fromNative($completePayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        //@todo conflict on same paymentid
        return false;
    }
}
