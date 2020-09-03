<?php declare(strict_types=1);

namespace Satbased\Accounting;

use Daikon\Boot\Service\ProcessManagerTrait;
use Daikon\Elasticsearch7\Query\TermFilter;
use Daikon\Interop\Assertion;
use Daikon\MessageBus\Channel\Subscription\MessageHandler\MessageHandlerInterface;
use Daikon\MessageBus\MessageBusInterface;
use Daikon\Metadata\MetadataInterface;
use Daikon\ValueObject\Uuid;
use Psr\Log\LoggerInterface;
use Satbased\Accounting\Account\Credit\CreditAccount;
use Satbased\Accounting\Account\Debit\DebitAccount;
use Satbased\Accounting\Account\Freeze\FreezeAccount;
use Satbased\Accounting\Account\Open\OpenAccount;
use Satbased\Accounting\Payment\Cancel\PaymentCancelled;
use Satbased\Accounting\Payment\Complete\PaymentCompleted;
use Satbased\Accounting\Payment\Fail\PaymentFailed;
use Satbased\Accounting\Payment\Make\PaymentMade;
use Satbased\Accounting\Payment\Settle\PaymentSettled;
use Satbased\Accounting\ReadModel\Standard\Account;
use Satbased\Accounting\ReadModel\Standard\AccountCollection;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Security\Profile\Close\ProfileClosed;
use Satbased\Security\Profile\Register\ProfileRegistered;

final class AccountManager implements MessageHandlerInterface
{
    use ProcessManagerTrait;

    private LoggerInterface $logger;

    private MessageBusInterface $messageBus;

    private AccountCollection $accountCollection;

    public function __construct(
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        AccountCollection $accountCollection
    ) {
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->accountCollection = $accountCollection;
    }

    public function whenProfileRegistered(ProfileRegistered $profileRegistered, MetadataInterface $metadata): void
    {
        $this->then(OpenAccount::fromNative([
            'accountId' => AccountId::PREFIX.'-'.Uuid::generate(),
            'profileId' => (string)$profileRegistered->getProfileId(),
            'openedAt' => (string)$profileRegistered->getRegisteredAt()
        ]), $metadata);
    }

    public function whenProfileClosed(ProfileClosed $profileClosed, MetadataInterface $metadata): void
    {
        // assumption there is only one account per profile
        /** @var Account $account */
        $account = $this->accountCollection->selectOne(
            TermFilter::fromNative(['profileId' => (string)$profileClosed->getProfileId()])
        )->getFirst();
        Assertion::isInstanceOf($account, Account::class);

        $this->then(FreezeAccount::fromNative([
            'accountId' => (string)$account->getAccountId(),
            'revision' => (string)$account->getRevision(),
            'frozenAt' => (string)$profileClosed->getClosedAt()
        ]), $metadata);
    }

    public function whenPaymentMade(PaymentMade $paymentMade, MetadataInterface $metadata): void
    {
        $account = $this->loadAccount((string)$paymentMade->getAccountId());
        $this->then(DebitAccount::fromNative([
            'accountId' => (string)$paymentMade->getAccountId(),
            'revision' => (string)$account->getRevision(),
            'paymentId' => (string)$paymentMade->getPaymentId(),
            'amount' => (string)$paymentMade->getAmount()->add($paymentMade->getFeeEstimate()),
            'debitedAt' => (string)$paymentMade->getRequestedAt()
        ]), $metadata);
    }

    public function whenPaymentSettled(PaymentSettled $paymentSettled, MetadataInterface $metadata): void
    {
        $account = $this->loadAccount((string)$paymentSettled->getAccountId());
        $this->then(CreditAccount::fromNative([
            'accountId' => (string)$paymentSettled->getAccountId(),
            'revision' => (string)$account->getRevision(),
            'paymentId' => (string)$paymentSettled->getPaymentId(),
            'amount' => (string)$paymentSettled->getAmount(),
            'creditedAt' => (string)$paymentSettled->getSettledAt()
        ]), $metadata);
    }

    public function whenPaymentCompleted(PaymentCompleted $paymentCompleted, MetadataInterface $metadata): void
    {
        $account = $this->loadAccount((string)$paymentCompleted->getAccountId());
        if ($paymentCompleted->getFeeRefund()->isPositive()) {
            $this->then(CreditAccount::fromNative([
                'accountId' => (string)$paymentCompleted->getAccountId(),
                'revision' => (string)$account->getRevision(),
                'paymentId' => (string)$paymentCompleted->getPaymentId(),
                'amount' => (string)$paymentCompleted->getFeeRefund(),
                'creditedAt' => (string)$paymentCompleted->getCompletedAt()
            ]), $metadata);
        }
    }

    public function whenPaymentCancelled(PaymentCancelled $paymentCancelled, MetadataInterface $metadata): void
    {
        if ($paymentCancelled->getState()->isMade()) {
            $account = $this->loadAccount((string)$paymentCancelled->getAccountId());
            $this->then(CreditAccount::fromNative([
                'accountId' => (string)$paymentCancelled->getAccountId(),
                'revision' => (string)$account->getRevision(),
                'paymentId' => (string)$paymentCancelled->getPaymentId(),
                'amount' => (string)$paymentCancelled->getAmount()->add($paymentCancelled->getFeeEstimate()),
                'creditedAt' => (string)$paymentCancelled->getCancelledAt()
            ]), $metadata);
        }
    }

    public function whenPaymentFailed(PaymentFailed $paymentFailed, MetadataInterface $metadata): void
    {
        $account = $this->loadAccount((string)$paymentFailed->getAccountId());
        $this->then(CreditAccount::fromNative([
            'accountId' => (string)$paymentFailed->getAccountId(),
            'revision' => (string)$account->getRevision(),
            'paymentId' => (string)$paymentFailed->getPaymentId(),
            'amount' => (string)$paymentFailed->getAmount()->add($paymentFailed->getFeeEstimate()),
            'creditedAt' => (string)$paymentFailed->getFailedAt()
        ]), $metadata);
    }

    private function loadAccount(string $accountId): Account
    {
        /** @var Account $account */
        $account = $this->accountCollection->byId($accountId)->getFirst();
        Assertion::isInstanceOf($account, Account::class);
        return $account;
    }
}
