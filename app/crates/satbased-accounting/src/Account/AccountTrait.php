<?php declare(strict_types=1);

namespace Satbased\Accounting\Account;

use Daikon\EventSourcing\Aggregate\AggregateRevision;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\BitcoinWallet;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\AccountState;
use Satbased\Security\ValueObject\ProfileId;

trait AccountTrait
{
    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }

    public function getRevision(): AggregateRevision
    {
        return $this->revision;
    }

    public function getProfileId(): ProfileId
    {
        return $this->profileId;
    }

    public function getWallet(): BitcoinWallet
    {
        return $this->wallet ?? BitcoinWallet::makeEmpty();
    }

    public function getOpenedAt(): Timestamp
    {
        return $this->openedAt;
    }

    public function getFrozenAt(): Timestamp
    {
        return $this->frozenAt ?? Timestamp::makeEmpty();
    }

    public function getState(): AccountState
    {
        return $this->state;
    }

    public function canBeFrozen(Timestamp $now): bool
    {
        return $this->state->isOpened();
    }
}
