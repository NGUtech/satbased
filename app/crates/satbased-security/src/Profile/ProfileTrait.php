<?php declare(strict_types=1);

namespace Satbased\Security\Profile;

use Daikon\EventSourcing\Aggregate\AggregateRevision;
use Daikon\ValueObject\Email;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\Timestamp;
use Satbased\Security\ValueObject\ProfileId;
use Satbased\Security\ValueObject\ProfileRole;
use Satbased\Security\ValueObject\ProfileState;
use Satbased\Security\ValueObject\ProfileTokenList;
use Satbased\Security\ValueObject\PasswordHash;

trait ProfileTrait
{
    public function getProfileId(): ProfileId
    {
        return $this->profileId;
    }

    public function getRevision(): AggregateRevision
    {
        return $this->revision;
    }

    public function getName(): Text
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function getLanguage(): Text
    {
        return $this->language;
    }

    public function getRole(): ProfileRole
    {
        return $this->role;
    }

    public function getState(): ProfileState
    {
        return $this->state;
    }

    public function getRegisteredAt(): Timestamp
    {
        return $this->registeredAt;
    }

    public function getVerifiedAt(): Timestamp
    {
        return $this->verifiedAt ?? Timestamp::makeEmpty();
    }

    public function getClosedAt(): Timestamp
    {
        return $this->closedAt ?? Timestamp::makeEmpty();
    }

    public function getTokens(): ProfileTokenList
    {
        return $this->tokens ?? ProfileTokenList::makeEmpty();
    }

    public function canBeLoggedIn(): bool
    {
        return !$this->state->isClosed();
    }

    public function canBeVerified(): bool
    {
        return $this->state->isPending();
    }

    public function canBeClosed(): bool
    {
        return !$this->state->isClosed();
    }

    public function canBePromoted(): bool
    {
        return $this->state->isVerified();
    }
}
