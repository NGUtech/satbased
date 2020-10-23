<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment;

use Daikon\Boot\Service\Provisioner\MessageBusProvisioner;
use Daikon\Config\ConfigProviderInterface;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\Interop\Assertion;
use Daikon\Interop\RuntimeException;
use Daikon\MessageBus\MessageBusInterface;
use Daikon\Metadata\MetadataInterface;
use Daikon\Money\Service\PaymentServiceInterface;
use Daikon\Money\Service\PaymentServiceMap;
use Daikon\Money\ValueObject\MoneyInterface;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\Timestamp;
use Daikon\ValueObject\Uuid;
use NGUtech\Bitcoin\Entity\BitcoinTransaction;
use NGUtech\Bitcoin\ValueObject\BitcoinWallet;
use NGUtech\Bitcoind\Service\BitcoindService;
use NGUtech\Lightning\Entity\LightningInvoice;
use NGUtech\Lightning\Service\LightningHoldServiceInterface;
use NGUtech\Lightning\Service\LightningServiceInterface;
use Satbased\Accounting\Entity\BalanceTransfer;
use Satbased\Accounting\Payment\Cancel\CancelPayment;
use Satbased\Accounting\Payment\Make\MakePayment;
use Satbased\Accounting\Payment\Request\RequestPayment;
use Satbased\Accounting\Payment\Rescan\PaymentRescanned;
use Satbased\Accounting\Payment\Select\SelectPayment;
use Satbased\Accounting\Payment\TransferService;
use Satbased\Accounting\ReadModel\Standard\Account;
use Satbased\Accounting\ReadModel\Standard\Payment;
use Satbased\Accounting\ValueObject\PaymentId;

final class PaymentService
{
    private ConfigProviderInterface $config;

    private MessageBusInterface $messageBus;

    private PaymentServiceMap $paymentServiceMap;

    public function __construct(
        ConfigProviderInterface $config,
        MessageBusInterface $messageBus,
        PaymentServiceMap $paymentServiceMap
    ) {
        $this->config = $config;
        $this->messageBus = $messageBus;
        $this->paymentServiceMap = $paymentServiceMap;
    }

    public function request(array $payload): RequestPayment
    {
        $this->assertInput($payload, ['references', 'accepts', 'amount', 'description', 'account', 'expires']);

        Assertion::false($payload['account']->getState()->isFrozen(), 'Account is frozen.');

        $requestPayment = RequestPayment::fromNative([
            'paymentId' => PaymentId::PREFIX.'-'.Uuid::generate(),
            'profileId' => (string)$payload['account']->getProfileId(),
            'accountId' => (string)$payload['account']->getAccountId(),
            'references' => $payload['references']->toNative(),
            'accepts' => $payload['accepts']->toNative(),
            'amount' => (string)$payload['amount'],
            'description' => (string)$payload['description'],
            'requestedAt' => (string)Timestamp::now(),
            'expiresAt' => (string)$payload['expires']
        ]);

        $this->dispatch($requestPayment);

        return $requestPayment;
    }

    public function make(array $payload): MakePayment
    {
        $this->assertInput($payload, ['references', 'amount', 'description', 'service', 'account', 'transaction']);

        Assertion::false($payload['account']->getState()->isFrozen(), 'Account is frozen.');

        /** @var PaymentServiceInterface $paymentService */
        $paymentService = $payload['service'];
        Assertion::isInstanceOf(
            $paymentService,
            PaymentServiceInterface::class,
            'Payment service cannot process request.'
        );

        /** @var MoneyInterface $amount */
        $amount = $payload['amount'];
        Assertion::true($paymentService->canSend($amount), 'Payment service cannot send given amount.');

        /** @var BitcoinWallet $wallet */
        $wallet = $payload['account']->getWallet();
        $feeEstimate = $payload['transaction']->unwrap()->getFeeEstimate();
        Assertion::true($wallet->hasBalance($amount->add($feeEstimate)), 'Insufficient balance.');

        $paymentId = PaymentId::PREFIX.'-'.Uuid::generate();
        $makePayment = MakePayment::fromNative([
            'paymentId' => $paymentId,
            'profileId' => (string)$payload['account']->getProfileId(),
            'accountId' => (string)$payload['account']->getAccountId(),
            'references' => $payload['references']->toNative(),
            'amount' => (string)$amount,
            'description' => (string)$payload['description'],
            'service' => $this->paymentServiceMap->find($paymentService),
            'transaction' => array_merge($payload['transaction']->toNative(), ['label' => $paymentId]),
            'requestedAt' => (string)Timestamp::now()
        ]);

        $this->dispatch($makePayment);

        return $makePayment;
    }

    public function select(array $payload): SelectPayment
    {
        $this->assertInput($payload, ['payment', 'service', 'account']);

        Assertion::false($payload['account']->getState()->isFrozen(), 'Account is frozen.');
        Assertion::false(
            $payload['service'] instanceof TransferService
            && $payload['account']->getAccountId()->equals($payload['payment']->getAccountId()),
            'Cannot transfer from same account.'
        );

        /** @var Payment $payment */
        $payment = $payload['payment'];
        $amount = $payment->getAmount();
        $now = Timestamp::now();
        Assertion::true($payment->canBeSelected($now), 'Payment cannot be selected.');
        Assertion::true($payload['service']->canRequest($amount), 'Payment service cannot request given amount.');

        /** @var BitcoinWallet $wallet */
        $wallet = $payload['account']->getWallet();
        Assertion::false(
            $payload['service'] instanceof TransferService
            && $wallet->getBalance($amount->getCurrency())->subtract($amount)->isNegative(),
            'Insufficient balance.'
        );

        /** @var PaymentServiceInterface $paymentService */
        $paymentService = $payload['service'];
        $paymentId = (string)$payment->getPaymentId();
        switch (true) {
            case $paymentService instanceof LightningServiceInterface:
                $transaction = LightningInvoice::fromNative([
                    'preimage' => $this->generatePreimage((string)$payload['secret'] ?: $paymentId),
                    'amount' => (string)$amount,
                    'label' => $paymentId,
                    'cltvExpiry' => 18,
                    'description' => (string)$payment->getDescription()
                ]);
                break;
            case $paymentService instanceof BitcoindService:
                $transaction = BitcoinTransaction::fromNative([
                    'amount' => (string)$amount,
                    'label' => $paymentId,
                    'comment' => (string)$payment->getDescription()
                ]);
                break;
            case $paymentService instanceof TransferService:
                $transaction = BalanceTransfer::fromNative([
                    'paymentId' => PaymentId::PREFIX.'-'.Uuid::generate(),
                    'profileId' => (string)$payload['account']->getProfileId(),
                    'accountId' => (string)$payload['account']->getAccountId(),
                    'amount' => (string)$amount
                ]);
                break;
            default:
                throw new RuntimeException('Unhandled money service.');
        }

        $selectPayment = SelectPayment::fromNative([
            'paymentId' => $paymentId,
            'revision' => (string)$payment->getRevision(),
            'service' => $this->paymentServiceMap->find($payload['service']),
            'transaction' => $payload['service']->request($transaction)->toNative(),
            'selectedAt' => (string)$now
        ]);

        $this->dispatch($selectPayment);

        return $selectPayment;
    }

    public function settle(Payment $payment): bool
    {
        $now = Timestamp::now();

        Assertion::true($payment->canBeSettled($now), 'Payment cannot be settled.');

        /** @var LightningHoldServiceInterface $lightningService */
        $lightningService = $this->paymentServiceMap->get((string)$payment->getService());
        Assertion::isInstanceOf(
            $lightningService,
            LightningHoldServiceInterface::class,
            'Lightning service cannot process settle.'
        );

        return $lightningService->settle($payment->getTransaction()->unwrap());
    }

    public function cancel(Payment $payment): ?CancelPayment
    {
        $now = Timestamp::now();
        Assertion::true($payment->canBeCancelled($now), 'Payment cannot be cancelled.');

        if ($payment->getState()->isReceived()) {
            /** @var LightningHoldServiceInterface $lightningService */
            $lightningService = $this->paymentServiceMap->get((string)$payment->getService());
            Assertion::isInstanceOf(
                $lightningService,
                LightningHoldServiceInterface::class,
                'Lightning service cannot process cancel.'
            );
            $lightningService->cancel($payment->getTransaction()->unwrap());
            return null;
        } else {
            $cancelPayment = CancelPayment::fromNative([
                'paymentId' => (string)$payment->getPaymentId(),
                'revision' => (string)$payment->getRevision(),
                'accountId' => (string)$payment->getAccountId(),
                'references' => $payment->getReferences()->toNative(),
                'amount' => (string)$payment->getAmount(),
                'feeEstimate' => (string)$payment->getFeeEstimate(),
                'state' => (string)$payment->getState(),
                'cancelledAt' => (string)$now
            ]);
            $this->dispatch($cancelPayment);
            return $cancelPayment;
        }
    }

    public function services(Payment $payment, Account $account): PaymentServiceMap
    {
        Assertion::false($account->getState()->isFrozen(), 'Account is frozen.');
        Assertion::true($payment->canBeSelected(Timestamp::now()), 'Payment service cannot be selected.');

        $amount = $payment->getAmount();
        $availableServices = $this->paymentServiceMap->availableForRequest($amount);
        $acceptedServices = $payment->getAccepts();
        if (!$acceptedServices->isEmpty()) {
            $availableServices = $availableServices->filter(
                fn (string $key): bool => $acceptedServices->find(Text::fromNative($key)) !== false
            );
        }

        // remove transfer service if not enough available funds in account
        if (!$account->getWallet()->getBalance($amount->getCurrency())->isGreaterThanOrEqual($amount)) {
            $availableServices = $availableServices->filter(
                fn(string $key, PaymentServiceInterface $paymentService): bool =>
                    !$paymentService instanceof TransferService
            );
        }

        // remove hold service options for non-owner
        if (!$payment->getProfileId()->equals($account->getProfileId())) {
            $availableServices = $availableServices->filter(
                fn(string $key, PaymentServiceInterface $paymentService): bool =>
                    !$paymentService instanceof LightningHoldServiceInterface
            );
        }

        return $availableServices;
    }

    public function rescan(Payment $payment): void
    {
        Assertion::true($payment->canBeRescanned(), 'Payment cannot be rescanned.');

        $paymentRescanned = PaymentRescanned::fromNative([
            'paymentId' => (string)$payment->getPaymentId(),
            'revision' => (string)$payment->getRevision()
        ]);

        $this->messageBus->publish($paymentRescanned, MessageBusProvisioner::EVENTS_CHANNEL);
    }

    private function generatePreimage(string $input): string
    {
        $secret = $this->config->get('crates.satbased.accounting.preimage_secret');
        Assertion::notBlank($secret, 'Preimage secret must not be blank.');
        return hash_hmac('sha256', $input, $secret);
    }

    private function dispatch(CommandInterface $command, MetadataInterface $metadata = null): void
    {
        $this->messageBus->publish($command, MessageBusProvisioner::COMMANDS_CHANNEL, $metadata);
    }

    private function assertInput(array $payload, array $expectedInput): void
    {
        $missingInput = array_diff($expectedInput, array_keys($payload));
        Assertion::true(empty($missingInput), "Missing required input '".implode(', ', $missingInput)."'.");
    }
}
