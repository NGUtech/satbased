<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Rescan;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Satbased\Accounting\Payment\PaymentMessageTrait;

final class PaymentRescanned implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use PaymentMessageTrait;

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
