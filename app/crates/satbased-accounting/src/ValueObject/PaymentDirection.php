<?php declare(strict_types=1);

namespace Satbased\Accounting\ValueObject;

use Daikon\Interop\Assertion;
use Daikon\ValueObject\ValueObjectInterface;

final class PaymentDirection implements ValueObjectInterface
{
    public const INCOMING = 'incoming';
    public const OUTGOING = 'outgoing';

    public const STATES = [
        self::INCOMING,
        self::OUTGOING
    ];

    private string $direction;

    /** @param string $value */
    public static function fromNative($value): self
    {
        Assertion::string($value);
        return new self($value);
    }

    /** @param self $comparator */
    public function equals($comparator): bool
    {
        Assertion::isInstanceOf($comparator, self::class);
        return $this->toNative() === $comparator->toNative();
    }

    public function isIncoming(): bool
    {
        return $this->direction === self::INCOMING;
    }

    public function isOutgoing(): bool
    {
        return $this->direction === self::OUTGOING;
    }

    public function toNative(): string
    {
        return $this->direction;
    }

    public function __toString(): string
    {
        return $this->direction;
    }

    private function __construct(string $direction)
    {
        Assertion::inArray($direction, self::STATES);
        $this->direction = $direction;
    }
}
