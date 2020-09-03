<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Request;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentRequested implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use RequestMessageTrait;

    public static function fromCommand(RequestPayment $requestPayment): self
    {
        return self::fromNative($requestPayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
