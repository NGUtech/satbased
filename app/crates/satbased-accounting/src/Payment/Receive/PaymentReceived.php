<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Receive;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentReceived implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use ReceiveMessageTrait;

    public static function fromCommand(ReceivePayment $receivePayment): self
    {
        return self::fromNative($receivePayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
