<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Send;

use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use Satbased\Accounting\Payment\PaymentMessageTrait;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\Transaction;

/**
 * @map(accountId, Satbased\Accounting\ValueObject\AccountId)
 * @map(references, Daikon\ValueObject\TextMap)
 * @map(transaction, Satbased\Accounting\ValueObject\Transaction)
 * @map(sentAt, Daikon\ValueObject\Timestamp)
 */
trait SendMessageTrait
{
    use PaymentMessageTrait;

    private AccountId $accountId;

    private TextMap $references;

    private Transaction $transaction;

    private Timestamp $sentAt;

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }

    public function getReferences(): TextMap
    {
        return $this->references;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getSentAt(): Timestamp
    {
        return $this->sentAt;
    }
}
