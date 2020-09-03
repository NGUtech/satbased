<?php declare(strict_types=1);

namespace Satbased\Security\ValueObject;

use Daikon\Interop\Assertion;
use Daikon\ValueObject\ValueObjectInterface;

final class PasswordHash implements ValueObjectInterface
{
    private const MAX_PASSWORD_LENGTH = 60;
    private const DEFAULT_COST = 10;

    private string $hash;

    public static function gen(string $password, int $cost = self::DEFAULT_COST): self
    {
        Assertion::maxLength($password, self::MAX_PASSWORD_LENGTH);
        return new self(self::encode($password, $cost));
    }

    /** @param string $hash */
    public static function fromNative($hash): self
    {
        Assertion::string($hash);
        return new self($hash);
    }

    /** @param self $comparator */
    public function equals($comparator): bool
    {
        Assertion::isInstanceOf($comparator, self::class);
        return $this->toNative() === $comparator->toNative();
    }

    public function toNative(): string
    {
        return $this->hash;
    }

    public function __toString(): string
    {
        return $this->hash;
    }

    public function getLength(): int
    {
        return strlen($this->hash);
    }

    public function verify(string $password): bool
    {
        Assertion::maxLength($password, self::MAX_PASSWORD_LENGTH);
        return password_verify($password, $this->hash);
    }

    private function __construct(string $hash)
    {
        Assertion::notEmpty($hash);
        $this->hash = $hash;
    }

    private static function encode(string $password, int $cost): string
    {
        Assertion::between($cost, 4, 31, 'Cost must be in the range of 4-31.');
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}
