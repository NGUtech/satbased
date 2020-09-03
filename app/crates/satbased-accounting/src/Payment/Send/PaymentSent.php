<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Send;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentSent implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use SendMessageTrait;

    public static function fromCommand(SendPayment $sendPayment): self
    {
        return self::fromNative($sendPayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
