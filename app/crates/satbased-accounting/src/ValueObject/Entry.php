<?php declare(strict_types=1);

namespace Satbased\Accounting\ValueObject;

use Daikon\Interop\Assertion;
use Daikon\ValueObject\Timestamp;
use Daikon\ValueObject\ValueObjectInterface;
use NGUtech\Bitcoin\ValueObject\Bitcoin;

final class Entry implements ValueObjectInterface
{
    private Bitcoin $amount;

    private Timestamp $enteredAt;

    /** @param array $state */
    public static function fromNative($state): self
    {
        Assertion::isArray($state, 'Must be an array.');
        Assertion::keyExists($state, 'amount');
        Assertion::keyExists($state, 'enteredAt');

        Assertion::notBlank($state['amount']);
        Assertion::notBlank($state['enteredAt']);

        return new self(Bitcoin::fromNative($state['amount']), Timestamp::fromNative($state['enteredAt']));
    }

    /** @param self $comparator */
    public function equals($comparator): bool
    {
        Assertion::isInstanceOf($comparator, self::class);
        return $this->toNative() === $comparator->toNative();
    }

    public function getAmount(): Bitcoin
    {
        return $this->amount;
    }

    public function getEnteredAt(): Timestamp
    {
        return $this->enteredAt;
    }

    public function toNative(): array
    {
        return [
            'amount' => (string)$this->amount,
            'enteredAt' => (string)$this->enteredAt
        ];
    }

    public function __toString(): string
    {
        return $this->enteredAt.':'.$this->amount;
    }

    private function __construct(Bitcoin $amount, Timestamp $enteredAt)
    {
        $this->amount = $amount;
        $this->enteredAt = $enteredAt;
    }
}
