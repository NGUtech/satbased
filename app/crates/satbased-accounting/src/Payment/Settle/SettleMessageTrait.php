<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Settle;

use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Payment\PaymentMessageTrait;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Security\ValueObject\ProfileId;

/**
 * @map(profileId, Satbased\Security\ValueObject\ProfileId)
 * @map(accountId, Satbased\Accounting\ValueObject\AccountId)
 * @map(amount, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(references, Daikon\ValueObject\TextMap)
 * @map(settledAt, Daikon\ValueObject\Timestamp)
 */
trait SettleMessageTrait
{
    use PaymentMessageTrait;

    private ProfileId $profileId;

    private AccountId $accountId;

    private Bitcoin $amount;

    private TextMap $references;

    private Timestamp $settledAt;

    public function getProfileId(): ProfileId
    {
        return $this->profileId;
    }

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

    public function getSettledAt(): Timestamp
    {
        return $this->settledAt;
    }
}
