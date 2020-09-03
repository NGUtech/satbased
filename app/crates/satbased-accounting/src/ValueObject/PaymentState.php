<?php declare(strict_types=1);

namespace Satbased\Accounting\ValueObject;

use Daikon\Interop\Assertion;
use Daikon\ValueObject\ValueObjectInterface;

final class PaymentState implements ValueObjectInterface
{
    public const REQUESTED = 'requested';
    public const MADE = 'made';
    public const SELECTED = 'selected';
    public const RECEIVED = 'received';
    public const SENT = 'sent';
    public const SETTLED = 'settled';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';
    public const FAILED = 'failed';

    public const STATES = [
        self::REQUESTED,
        self::MADE,
        self::SELECTED,
        self::RECEIVED,
        self::SENT,
        self::SETTLED,
        self::COMPLETED,
        self::CANCELLED,
        self::FAILED
    ];

    private string $state;

    /** @param string $state */
    public static function fromNative($state): self
    {
        Assertion::string($state);
        return new self($state);
    }

    public function toNative(): string
    {
        return $this->state;
    }

    /** @param self $comparator */
    public function equals($comparator): bool
    {
        Assertion::isInstanceOf($comparator, self::class);
        return $this->toNative() === $comparator->toNative();
    }

    public function isRequested(): bool
    {
        return $this->state === self::REQUESTED;
    }

    public function isMade(): bool
    {
        return $this->state === self::MADE;
    }

    public function isSelected(): bool
    {
        return $this->state === self::SELECTED;
    }

    public function isReceived(): bool
    {
        return $this->state === self::RECEIVED;
    }

    public function isSent(): bool
    {
        return $this->state === self::SENT;
    }

    public function isSettled(): bool
    {
        return $this->state === self::SETTLED;
    }

    public function isCompleted(): bool
    {
        return $this->state === self::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->state === self::CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this->state === self::FAILED;
    }

    public function __toString(): string
    {
        return $this->state;
    }

    private function __construct(string $state)
    {
        Assertion::inArray($state, self::STATES);
        $this->state = $state;
    }
}
