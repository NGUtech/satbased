<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Fail;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentFailed implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use FailMessageTrait;

    public static function fromCommand(FailPayment $failPayment): self
    {
        return self::fromNative($failPayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
