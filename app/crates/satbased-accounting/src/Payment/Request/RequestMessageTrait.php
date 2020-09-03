<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Request;

use Daikon\ValueObject\Text;
use Daikon\ValueObject\TextList;
use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Payment\PaymentMessageTrait;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Security\ValueObject\ProfileId;

/**
 * @map(profileId, Satbased\Security\ValueObject\ProfileId)
 * @map(accountId, Satbased\Accounting\ValueObject\AccountId)
 * @map(references, Daikon\ValueObject\TextMap)
 * @map(accepts, Daikon\ValueObject\TextList)
 * @map(amount, NGUtech\Bitcoin\ValueObject\Bitcoin)
 * @map(description, Daikon\ValueObject\Text)
 * @map(requestedAt, Daikon\ValueObject\Timestamp)
 * @map(expiresAt, Daikon\ValueObject\Timestamp)
 */
trait RequestMessageTrait
{
    use PaymentMessageTrait;

    private ProfileId $profileId;

    private AccountId $accountId;

    private TextMap $references;

    private TextList $accepts;

    private Bitcoin $amount;

    private Text $description;

    private Timestamp $requestedAt;

    private Timestamp $expiresAt;

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

    public function getAccepts(): TextList
    {
        return $this->accepts;
    }

    public function getAmount(): Bitcoin
    {
        return $this->amount;
    }

    public function getDescription(): Text
    {
        return $this->description;
    }

    public function getRequestedAt(): Timestamp
    {
        return $this->requestedAt;
    }

    public function getExpiresAt(): Timestamp
    {
        return $this->expiresAt;
    }
}
