<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Complete;

use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Payment\PaymentMessageTrait;
use Satbased\Accounting\ValueObject\AccountId;

/**
 * @map(accountId, Satbased\Accounting\ValueObject\AccountId)
 * @map(amount, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(feeRefund, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(references, Daikon\ValueObject\TextMap)
 * @map(completedAt, Daikon\ValueObject\Timestamp)
 */
trait CompleteMessageTrait
{
    use PaymentMessageTrait;

    private AccountId $accountId;

    private Bitcoin $amount;

    private Bitcoin $feeRefund;

    private TextMap $references;

    private Timestamp $completedAt;

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }

    public function getAmount(): Bitcoin
    {
        return $this->amount;
    }

    public function getFeeRefund(): Bitcoin
    {
        return $this->feeRefund;
    }

    public function getReferences(): TextMap
    {
        return $this->references;
    }

    public function getCompletedAt(): Timestamp
    {
        return $this->completedAt;
    }
}
