<?php declare(strict_types=1);

namespace Satbased\Accounting\ValueObject;

use Daikon\Interop\Assertion;
use Daikon\Money\Entity\TransactionInterface;
use Daikon\ValueObject\ValueObjectInterface;
use NGUtech\Bitcoin\Entity\BitcoinTransaction;
use NGUtech\Lightning\Entity\LightningInvoice;
use NGUtech\Lightning\Entity\LightningPayment;

final class Transaction implements ValueObjectInterface
{
    private TransactionInterface $transaction;

    /** @param array $state */
    public static function fromNative($state): self
    {
        Assertion::keyExists($state, TransactionInterface::TYPE_KEY);
        $transactionType = $state[TransactionInterface::TYPE_KEY];
        $transaction = ([$transactionType, 'fromNative'])($state);
        return new self($transaction);
    }

    public function toNative(): array
    {
        return $this->transaction->toNative();
    }

    /** @param self $comparator */
    public function equals($comparator): bool
    {
        Assertion::isInstanceOf($comparator, self::class);
        return $this->toNative() === $comparator->toNative();
    }

    public function __toString(): string
    {
        return (string)$this->transaction;
    }

    /** @return BitcoinTransaction|LightningInvoice|LightningPayment */
    public function unwrap(): TransactionInterface
    {
        return $this->transaction;
    }

    public static function wrap(TransactionInterface $transaction): self
    {
        return new self($transaction);
    }

    private function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }
}
