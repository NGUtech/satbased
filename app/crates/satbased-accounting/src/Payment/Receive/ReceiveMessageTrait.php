<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Receive;

use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Payment\PaymentMessageTrait;
use Satbased\Accounting\ValueObject\AccountId;

/**
 * @map(accountId, Satbased\Accounting\ValueObject\AccountId)
 * @map(amount, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(references, Daikon\ValueObject\TextMap)
 * @map(receivedAt, Daikon\ValueObject\Timestamp)
 */
trait ReceiveMessageTrait
{
    use PaymentMessageTrait;

    private AccountId $accountId;

    private Bitcoin $amount;

    private TextMap $references;

    private Timestamp $receivedAt;

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }

    public function getAmount(): Bitcoin
    {
        return $this->amount;
    }

    public function getReferences(): TextMap
    {
        return $this->references;
    }

    public function getReceivedAt(): Timestamp
    {
        return $this->receivedAt;
    }
}
