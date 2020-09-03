<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Make;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentMade implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use MakeMessageTrait;

    public static function fromCommand(MakePayment $makePayment): self
    {
        return self::fromNative($makePayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
