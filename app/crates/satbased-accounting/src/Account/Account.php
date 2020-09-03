<?php declare(strict_types=1);

namespace Satbased\Accounting\Account;

use Daikon\EventSourcing\Aggregate\AggregateRoot;
use Daikon\Interop\Assertion;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\BitcoinWallet;
use Satbased\Accounting\Account\Credit\AccountCredited;
use Satbased\Accounting\Account\Credit\CreditAccount;
use Satbased\Accounting\Account\Debit\AccountDebited;
use Satbased\Accounting\Account\Debit\DebitAccount;
use Satbased\Accounting\Account\Freeze\AccountFrozen;
use Satbased\Accounting\Account\Freeze\FreezeAccount;
use Satbased\Accounting\Account\Open\AccountOpened;
use Satbased\Accounting\Account\Open\OpenAccount;
use Satbased\Accounting\ValueObject\AccountState;
use Satbased\Accounting\ValueObject\Entries;
use Satbased\Security\ValueObject\ProfileId;

final class Account extends AggregateRoot
{
    use AccountTrait;

    private const ENTRIES_INTERVAL = '+1 day';

    private ProfileId $profileId;

    private Entries $credits;

    private Entries $debits;

    private BitcoinWallet $wallet;

    private Timestamp $openedAt;

    private Timestamp $frozenAt;

    private AccountState $state;

    public static function open(OpenAccount $openAccount): self
    {
        return (new self($openAccount->getAccountId()))
            ->reflectThat(AccountOpened::fromCommand($openAccount));
    }

    public function credit(CreditAccount $creditAccount): self
    {
        Assertion::false($this->state->isFrozen(), 'Account is frozen.');
        Assertion::true($creditAccount->getAmount()->isPositive(), 'Credit amount must be positive.');
        Assertion::false($this->credits->has((string)$creditAccount->getPaymentId()), 'Credit already exists.');

        return $this->reflectThat(AccountCredited::fromCommand($creditAccount));
    }

    public function debit(DebitAccount $debitAccount): self
    {
        Assertion::false($this->state->isFrozen(), 'Account is frozen.');
        Assertion::true($debitAccount->getAmount()->isPositive(), 'Debit amount must be positive.');
        Assertion::false($this->debits->has((string)$debitAccount->getPaymentId()), 'Debit already exists.');

        return $this->reflectThat(AccountDebited::fromCommand($debitAccount));
    }

    public function freeze(FreezeAccount $freezeAccount): self
    {
        Assertion::true($this->canBeFrozen($freezeAccount->getFrozenAt()), 'Account cannot be frozen.');

        return $this->reflectThat(AccountFrozen::fromCommand($freezeAccount));
    }

    protected function whenAccountOpened(AccountOpened $accountOpened): void
    {
        $this->profileId = $accountOpened->getProfileId();
        $this->openedAt = $accountOpened->getOpenedAt();
        $this->credits = Entries::makeEmpty();
        $this->debits = Entries::makeEmpty();
        $this->wallet = BitcoinWallet::makeEmpty();
        $this->frozenAt = Timestamp::makeEmpty();
        $this->state = AccountState::fromNative(AccountState::OPENED);
    }

    protected function whenAccountCredited(AccountCredited $accountCredited): void
    {
        $this->credits = $this->credits->withEntry(
            $accountCredited->getPaymentId(),
            $accountCredited->getAmount(),
            $accountCredited->getCreditedAt()
        )->since(self::ENTRIES_INTERVAL);
        $this->wallet = $this->wallet->credit($accountCredited->getAmount());
    }

    protected function whenAccountDebited(AccountDebited $accountDebited): void
    {
        $this->debits = $this->debits->withEntry(
            $accountDebited->getPaymentId(),
            $accountDebited->getAmount(),
            $accountDebited->getDebitedAt()
        )->since(self::ENTRIES_INTERVAL);
        $this->wallet = $this->wallet->debit($accountDebited->getAmount());
    }

    protected function whenAccountFrozen(AccountFrozen $accountFrozen): void
    {
        $this->frozenAt = $accountFrozen->getFrozenAt();
        $this->state = AccountState::fromNative(AccountState::FROZEN);
    }
}
