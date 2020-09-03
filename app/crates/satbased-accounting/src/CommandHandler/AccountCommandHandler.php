<?php declare(strict_types=1);

namespace Satbased\Accounting\CommandHandler;

use Daikon\EventSourcing\Aggregate\Command\CommandHandler;
use Daikon\Metadata\MetadataInterface;
use Satbased\Accounting\Account\Account;
use Satbased\Accounting\Account\Credit\CreditAccount;
use Satbased\Accounting\Account\Debit\DebitAccount;
use Satbased\Accounting\Account\Freeze\FreezeAccount;
use Satbased\Accounting\Account\Open\OpenAccount;

final class AccountCommandHandler extends CommandHandler
{
    protected function handleOpenAccount(OpenAccount $openAccount, MetadataInterface $metadata): array
    {
        return [Account::open($openAccount), $metadata];
    }

    protected function handleCreditAccount(CreditAccount $creditAccount, MetadataInterface $metadata): array
    {
        /** @var Account $account */
        $account = $this->checkout($creditAccount->getAccountId(), $creditAccount->getKnownAggregateRevision());
        return [$account->credit($creditAccount), $metadata];
    }

    protected function handleDebitAccount(DebitAccount $debitAccount, MetadataInterface $metadata): array
    {
        /** @var Account $account */
        $account = $this->checkout($debitAccount->getAccountId(), $debitAccount->getKnownAggregateRevision());
        return [$account->debit($debitAccount), $metadata];
    }

    protected function handleFreezeAccount(FreezeAccount $freezeAccount, MetadataInterface $metadata): array
    {
        /** @var Account $account */
        $account = $this->checkout($freezeAccount->getAccountId(), $freezeAccount->getKnownAggregateRevision());
        return [$account->freeze($freezeAccount), $metadata];
    }
}
