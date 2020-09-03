<?php declare(strict_types=1);

namespace Satbased\Security\ValueObject;

use Daikon\Interop\Assertion;
use Daikon\ValueObject\ValueObjectInterface;

final class ProfileRole implements ValueObjectInterface
{
    public const CUSTOMER = 'customer';
    public const STAFF = 'staff';
    public const ADMIN = 'admin';

    public const ROLES = [
        self::CUSTOMER,
        self::STAFF,
        self::ADMIN
    ];

    private string $role;

    /** @param string $role */
    public static function fromNative($role): self
    {
        Assertion::string($role, 'Must be a string.');
        return new self($role);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::STAFF || $this->role === self::ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::CUSTOMER;
    }

    public function toNative(): string
    {
        return $this->role;
    }

    /** @param self $comparator */
    public function equals($comparator): bool
    {
        Assertion::isInstanceOf($comparator, self::class);
        return $this->toNative() === $comparator->toNative();
    }

    public function __toString(): string
    {
        return $this->role;
    }

    private function __construct(string $role)
    {
        Assertion::inArray($role, self::ROLES, 'Invalid role.');
        $this->role = $role;
    }
}
