<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment;

use Daikon\EventSourcing\Aggregate\AggregateRevision;
use Daikon\Interop\FromToNativeTrait;
use Satbased\Accounting\ValueObject\PaymentId;

/**
 * @id(paymentId, Satbased\Accounting\ValueObject\PaymentId)
 * @rev(revision, Daikon\EventSourcing\Aggregate\AggregateRevision)
 */
trait PaymentMessageTrait
{
    use FromToNativeTrait;

    private PaymentId $paymentId;

    private AggregateRevision $revision;

    public function getPaymentId(): PaymentId
    {
        return $this->paymentId;
    }
}
