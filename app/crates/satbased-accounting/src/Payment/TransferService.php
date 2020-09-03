<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment;

use Daikon\Interop\Assertion;
use Daikon\Money\Service\PaymentServiceInterface;
use Daikon\Money\ValueObject\MoneyInterface;
use Daikon\ValueObject\Uuid;
use Satbased\Accounting\Entity\BalanceTransfer;

final class TransferService implements PaymentServiceInterface
{
    public function request(BalanceTransfer $balanceTransfer): BalanceTransfer
    {
        Assertion::true(
            $this->canRequest($balanceTransfer->getAmount()),
            'Transfer service cannot request given amount.'
        );

        return $balanceTransfer->withValue('id', Uuid::generate());
    }

    public function send(BalanceTransfer $balanceTransfer): BalanceTransfer
    {
        Assertion::true(
            $this->canSend($balanceTransfer->getAmount()),
            'Transfer service cannot send given amount.'
        );

        return $balanceTransfer;
    }

    public function canRequest(MoneyInterface $amount): bool
    {
        // No minimum
        return (bool)($this->settings['request']['enabled'] ?? true);
    }

    public function canSend(MoneyInterface $amount): bool
    {
        // No minimum
        return (bool)($this->settings['send']['enabled'] ?? true);
    }
}
