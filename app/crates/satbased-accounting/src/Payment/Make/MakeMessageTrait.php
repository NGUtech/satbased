<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Make;

use Daikon\ValueObject\Text;
use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Payment\PaymentMessageTrait;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\Transaction;
use Satbased\Security\ValueObject\ProfileId;

/**
 * @map(profileId, Satbased\Security\ValueObject\ProfileId)
 * @map(accountId, Satbased\Accounting\ValueObject\AccountId)
 * @map(references, Daikon\ValueObject\TextMap)
 * @map(amount, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(description, Daikon\ValueObject\Text)
 * @map(service, Daikon\ValueObject\Text)
 * @map(transaction, Satbased\Accounting\ValueObject\Transaction)
 * @map(requestedAt, Daikon\ValueObject\Timestamp)
 */
trait MakeMessageTrait
{
    use PaymentMessageTrait;

    private ProfileId $profileId;

    private AccountId $accountId;

    private TextMap $references;

    private Bitcoin $amount;

    private Text $description;

    private Text $service;

    private Transaction $transaction;

    private Timestamp $requestedAt;

    public function getProfileId(): ProfileId
    {
        return $this->profileId;
    }

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }

    public function getReferences(): TextMap
    {
        return $this->references;
    }

    public function getAmount(): Bitcoin
    {
        return $this->amount;
    }

    public function getDescription(): Text
    {
        return $this->description;
    }

    public function getService(): Text
    {
        return $this->service;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getFeeEstimate(): Bitcoin
    {
        return $this->transaction->unwrap()->getFeeEstimate();
    }

    public function getRequestedAt(): Timestamp
    {
        return $this->requestedAt;
    }
}
