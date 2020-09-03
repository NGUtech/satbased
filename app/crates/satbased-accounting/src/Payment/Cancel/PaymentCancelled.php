<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Cancel;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentCancelled implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use CancelMessageTrait;

    public static function fromCommand(CancelPayment $cancelPayment): self
    {
        return self::fromNative($cancelPayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
