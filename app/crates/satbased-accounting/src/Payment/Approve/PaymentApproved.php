<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Approve;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class PaymentApproved implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use ApproveMessageTrait;

    public static function fromCommand(ApprovePayment $approvePayment): self
    {
        return self::fromNative($approvePayment->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
