<?php declare(strict_types=1);

namespace Satbased\Accounting\ValueObject;

use Daikon\ValueObject\Timestamp;
use Daikon\ValueObject\ValueObjectMap;
use NGUtech\Bitcoin\ValueObject\Bitcoin;

/**
 * @type(Satbased\Accounting\ValueObject\Entry)
 */
final class Entries extends ValueObjectMap
{
    public function withEntry(PaymentId $paymentId, Bitcoin $amount, Timestamp $enteredAt): self
    {
        return $this->with((string)$paymentId, Entry::fromNative([
            'amount' => (string)$amount,
            'enteredAt' => (string)$enteredAt
        ]));
    }

    public function since(string $interval): self
    {
        $time = Timestamp::now()->modify($interval);
        return $this->filter(fn(string $key, Entry $entry): bool => $entry->getEnteredAt()->isAfter($time));
    }
}
