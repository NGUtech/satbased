<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment;

use Daikon\EventSourcing\Aggregate\AggregateRevision;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\TextList;
use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\PaymentDirection;
use Satbased\Accounting\ValueObject\PaymentId;
use Satbased\Accounting\ValueObject\PaymentState;
use Satbased\Accounting\ValueObject\Transaction;
use Satbased\Security\ValueObject\ProfileId;

trait PaymentTrait
{
    public function getPaymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function getRevision(): AggregateRevision
    {
        return $this->revision;
    }

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

    public function getAccepts(): ?TextList
    {
        return $this->accepts;
    }

    public function getAmount(): Bitcoin
    {
        return $this->amount;
    }

    public function getDescription(): Text
    {
        return $this->description ?? Text::makeEmpty();
    }

    public function getService(): Text
    {
        return $this->service ?? Text::makeEmpty();
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function getFeeEstimate(): Bitcoin
    {
        if (!$this->transaction) {
            return Bitcoin::zero();
        }
        return $this->transaction->unwrap()->getFeeEstimate();
    }

    public function getFeeRefund(): Bitcoin
    {
        if (!$this->transaction) {
            return Bitcoin::zero();
        }
        return $this->transaction->unwrap()->getFeeRefund();
    }

    public function getState(): PaymentState
    {
        return $this->state;
    }

    public function getDirection(): PaymentDirection
    {
        return $this->direction;
    }

    public function getRequestedAt(): Timestamp
    {
        return $this->requestedAt;
    }

    public function getExpiresAt(): Timestamp
    {
        return $this->expiresAt ?? Timestamp::makeEmpty();
    }

    public function getSelectedAt(): Timestamp
    {
        return $this->selectedAt ?? Timestamp::makeEmpty();
    }

    public function getReceivedAt(): Timestamp
    {
        return $this->receivedAt ?? Timestamp::makeEmpty();
    }

    public function getSettledAt(): Timestamp
    {
        return $this->settledAt ?? Timestamp::makeEmpty();
    }

    public function getCompletedAt(): Timestamp
    {
        return $this->completedAt ?? Timestamp::makeEmpty();
    }

    public function getCancelledAt(): Timestamp
    {
        return $this->cancelledAt ?? Timestamp::makeEmpty();
    }

    public function getSentAt(): Timestamp
    {
        return $this->sentAt ?? Timestamp::makeEmpty();
    }

    public function getFailedAt(): Timestamp
    {
        return $this->failedAt ?? Timestamp::makeEmpty();
    }

    public function canBeSelected(Timestamp $time): bool
    {
        return $this->state->isRequested()
            && ($this->getExpiresAt()->isEmpty() || $this->getExpiresAt()->isAfter($time));
    }

    public function canBeUpdated(Timestamp $time): bool
    {
        //@todo check expiry
        return $this->state->isSelected();
    }

    public function canBeReceived(Timestamp $time): bool
    {
        //@todo check expiry
        return $this->state->isSelected();
    }

    public function canBeSettled(Timestamp $time): bool
    {
        //@todo check expiry
        return $this->state->isSelected() || $this->state->isReceived();
    }

    public function canBeCompleted(Timestamp $time): bool
    {
        //@todo check expiry
        return $this->state->isSent();
    }

    public function canBeSent(Timestamp $time): bool
    {
        //@todo check expiry
        return $this->state->isMade();
    }

    public function canBeCancelled(Timestamp $time): bool
    {
        //@todo check expiry
        return $this->state->isRequested() || $this->state->isReceived() || $this->state->isMade();
    }

    public function canBeFailed(): bool
    {
        return $this->state->isMade() || $this->state->isSent();
    }

    public function canBeRescanned(): bool
    {
        //@todo use a rescan timestamp to prevent spam?
        return $this->state->isSelected() || $this->state->isSent();
    }
}
