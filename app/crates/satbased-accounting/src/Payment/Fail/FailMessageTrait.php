<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Fail;

use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Payment\PaymentMessageTrait;
use Satbased\Accounting\ValueObject\AccountId;

/**
 * @map(accountId, Satbased\Accounting\ValueObject\AccountId)
 * @map(amount, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(feeEstimate, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(references, Daikon\ValueObject\TextMap)
 * @map(failedAt, Daikon\ValueObject\Timestamp)
 */
trait FailMessageTrait
{
    use PaymentMessageTrait;

    private AccountId $accountId;

    private Bitcoin $amount;

    private Bitcoin $feeEstimate;

    private TextMap $references;

    private Timestamp $failedAt;

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

    public function getFailedAt(): Timestamp
    {
        return $this->failedAt;
    }
}
