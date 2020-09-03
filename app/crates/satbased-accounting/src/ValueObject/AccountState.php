<?php declare(strict_types=1);

namespace Satbased\Accounting\ValueObject;

use Daikon\Interop\Assertion;
use Daikon\ValueObject\ValueObjectInterface;

final class AccountState implements ValueObjectInterface
{
    public const OPENED = 'opened';
    public const FROZEN = 'frozen';

    public const STATES = [
        self::OPENED,
        self::FROZEN
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

    public function isOpened(): bool
    {
        return $this->state === self::OPENED;
    }

    public function isFrozen(): bool
    {
        return $this->state === self::FROZEN;
    }

    public function __toString(): string
    {
        return $this->state;
    }

    private function __construct(string $state = self::OPENED)
    {
        Assertion::inArray($state, self::STATES);
        $this->state = $state;
    }
}
