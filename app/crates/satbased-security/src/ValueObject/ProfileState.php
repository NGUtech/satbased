<?php declare(strict_types=1);

namespace Satbased\Security\ValueObject;

use Daikon\Interop\Assertion;
use Daikon\ValueObject\ValueObjectInterface;

final class ProfileState implements ValueObjectInterface
{
    public const PENDING = 'pending';
    public const VERIFIED = 'verified';
    public const CLOSED = 'closed';

    public const STATES = [
        self::PENDING,
        self::VERIFIED,
        self::CLOSED
    ];

    private string  $state;

    /** @param string $state */
    public static function fromNative($state): self
    {
        Assertion::string($state, 'Must be a string.');
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

    public function isPending(): bool
    {
        return $this->state === self::PENDING;
    }

    public function isVerified(): bool
    {
        return $this->state === self::VERIFIED;
    }

    public function isClosed(): bool
    {
        return $this->state === self::CLOSED;
    }

    public function __toString(): string
    {
        return $this->state;
    }

    private function __construct(string $state)
    {
        Assertion::inArray($state, self::STATES, 'Invalid state.');
        $this->state = $state;
    }
}
