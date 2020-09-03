<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Select;

use Daikon\ValueObject\Text;
use Daikon\ValueObject\Timestamp;
use Satbased\Accounting\Payment\PaymentMessageTrait;
use Satbased\Accounting\ValueObject\Transaction;

/**
 * @map(service, Daikon\ValueObject\Text)
 * @map(transaction, Satbased\Accounting\ValueObject\Transaction)
 * @map(selectedAt, Daikon\ValueObject\Timestamp)
 */
trait SelectMessageTrait
{
    use PaymentMessageTrait;

    private Text $service;

    private Transaction $transaction;

    private Timestamp $selectedAt;

    public function getService(): Text
    {
        return $this->service;
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getSelectedAt(): Timestamp
    {
        return $this->selectedAt;
    }
}
