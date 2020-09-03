<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Cancel;

use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Payment\PaymentMessageTrait;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\PaymentState;

/**
 * @map(accountId, Satbased\Accounting\ValueObject\AccountId)
 * @map(amount, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(feeEstimate, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(references, Daikon\ValueObject\TextMap)
 * @map(state, Satbased\Accounting\ValueObject\PaymentState)
 * @map(cancelledAt, Daikon\ValueObject\Timestamp)
 */
trait CancelMessageTrait
{
    use PaymentMessageTrait;

    private AccountId $accountId;

    private Bitcoin $amount;

    private Bitcoin $feeEstimate;

    private TextMap $references;

    private PaymentState $state;

    private Timestamp $cancelledAt;

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }

    public function getAmount(): Bitcoin
    {
        return $this->amount;
    }

    public function getFeeEstimate(): Bitcoin
    {
        return $this->feeEstimate;
    }

    public function getReferences(): TextMap
    {
        return $this->references;
    }

    public function getState(): PaymentState
    {
        return $this->state;
    }

    public function getCancelledAt(): Timestamp
    {
        return $this->cancelledAt;
    }
}
