<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Credit;

use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Account\AccountMessageTrait;
use Satbased\Accounting\ValueObject\PaymentId;

/**
 * @map(paymentId, Satbased\Accounting\ValueObject\PaymentId)
 * @map(amount, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(creditedAt, Daikon\ValueObject\Timestamp)
 */
trait CreditMessageTrait
{
    use AccountMessageTrait;

    private PaymentId $paymentId;

    private Bitcoin $amount;

    private Timestamp $creditedAt;

    public function getPaymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function getAmount(): Bitcoin
    {
        return $this->amount;
    }

    public function getCreditedAt(): Timestamp
    {
        return $this->creditedAt;
    }
}
