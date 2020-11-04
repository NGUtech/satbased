<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Update;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentUpdated implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use UpdateMessageTrait;

    public static function fromCommand(UpdatePayment $updatePayment): self
    {
        return self::fromNative($updatePayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
