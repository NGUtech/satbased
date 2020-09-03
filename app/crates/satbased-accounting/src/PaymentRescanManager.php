<?php declare(strict_types=1);

namespace Satbased\Accounting;

use Daikon\Boot\Service\ProcessManagerTrait;
use Daikon\Interop\Assertion;
use Daikon\MessageBus\Channel\Subscription\MessageHandler\MessageHandlerInterface;
use Daikon\MessageBus\MessageBusInterface;
use Daikon\Metadata\MetadataInterface;
use Daikon\Money\Service\PaymentServiceMap;
use Daikon\ValueObject\Timestamp;
use DomainException;
use NGUtech\Bitcoin\Service\BitcoinServiceInterface;
use NGUtech\Lightning\Service\LightningServiceInterface;
use NGUtech\Lightningd\Service\LightningdService;
use NGUtech\Lnd\Service\LndService;
use Psr\Log\LoggerInterface;
use Satbased\Accounting\Payment\Complete\CompletePayment;
use Satbased\Accounting\Payment\Fail\FailPayment;
use Satbased\Accounting\Payment\Rescan\PaymentRescanned;
use Satbased\Accounting\Payment\Settle\SettlePayment;
use Satbased\Accounting\ReadModel\Standard\Payment;
use Satbased\Accounting\ReadModel\Standard\PaymentCollection;

final class PaymentRescanManager implements MessageHandlerInterface
{
    use ProcessManagerTrait;

    private LoggerInterface $logger;

    private MessageBusInterface $messageBus;

    private PaymentCollection $paymentCollection;

    private PaymentServiceMap $paymentServiceMap;

    public function __construct(
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        PaymentCollection $paymentCollection,
        PaymentServiceMap $paymentServiceMap
    ) {
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->paymentCollection = $paymentCollection;
        $this->paymentServiceMap = $paymentServiceMap;
    }

    protected function whenPaymentRescanned(PaymentRescanned $paymentRescanned, MetadataInterface $metadata): void
    {
        $payment = $this->loadPayment((string)$paymentRescanned->getPaymentId());
        if ($payment->canBeRescanned()) {
            if ($payment->getState()->isSelected()) {
                $this->handleSelectedPaymentRescan($payment, $metadata);
            } elseif ($payment->getState()->isSent()) {
                $this->handleSentPaymentRescan($payment, $metadata);
            }
        }
    }

    private function handleSelectedPaymentRescan(Payment $payment, MetadataInterface $metadata): void
    {
        $now = Timestamp::now();
        $service = $this->paymentServiceMap->get((string)$payment->getService());
        $paymentTransaction = $payment->getTransaction()->unwrap();
        switch (true) {
            case $service instanceof LightningServiceInterface:
                /** @var LightningServiceInterface $service */
                switch (true) {
                    case $service instanceof LndService:
                        $lightningPayment = $service->getInvoice((string)$paymentTransaction->getPreimageHash());
                        break;
                    case $service instanceof LightningdService:
                        $lightningPayment = $service->getInvoice((string)$paymentTransaction->getLabel());
                        break;
                    default:
                        throw new DomainException(sprintf("Unhandled service '%s'.", get_class($service)));
                }
                if ($lightningPayment && $lightningPayment->getState()->isSettled()) {
                    $this->then(SettlePayment::fromNative([
                        'paymentId' => (string)$payment->getPaymentId(),
                        'revision' => (string)$payment->getRevision(),
                        'accountId' => (string)$payment->getAccountId(),
                        'amount' => (string)$payment->getAmount(),
                        'references' => $payment->getReferences()->toNative(),
                        'settledAt' => (string)$now
                    ]), $metadata);
                }
                break;
            case $service instanceof BitcoinServiceInterface:
                /** @var BitcoinServiceInterface $service */
                $address = $paymentTransaction->getOutputs()->first()->getAddress(); //Assume one address to check
                $confirmedBalance = $service->getConfirmedBalance($address, $paymentTransaction->getConfTarget());
                if ($confirmedBalance->isGreaterThanOrEqual($paymentTransaction->getAmount())) {
                    $this->then(SettlePayment::fromNative([
                        'paymentId' => (string)$payment->getPaymentId(),
                        'revision' => (string)$payment->getRevision(),
                        'accountId' => (string)$payment->getAccountId(),
                        'amount' => (string)$payment->getAmount(), //don't credit overpayment
                        'references' => $payment->getReferences()->toNative(),
                        'settledAt' => (string)$now
                    ]), $metadata);
                }
                break;
        }
    }

    private function handleSentPaymentRescan(Payment $payment, MetadataInterface $metadata): void
    {
        $now = Timestamp::now();
        $service = $this->paymentServiceMap->get((string)$payment->getService());
        $paymentTransaction = $payment->getTransaction()->unwrap();
        switch (true) {
            case $service instanceof LightningServiceInterface:
                /** @var LightningServiceInterface $service */
                $lightningPayment = $service->getPayment($paymentTransaction->getPreimageHash());
                if ($lightningPayment) {
                    if ($lightningPayment->getState()->isCompleted()) {
                        $this->then(CompletePayment::fromNative([
                            'paymentId' => (string)$payment->getPaymentId(),
                            'revision' => (string)$payment->getRevision(),
                            'accountId' => (string)$payment->getAccountId(),
                            'amount' => (string)$payment->getAmount(),
                            'feeRefund' => (string)$paymentTransaction->getFeeEstimate()->subtract(
                                $lightningPayment->getFeeSettled()
                            ),
                            'references' => $payment->getReferences()->toNative(),
                            'completedAt' => (string)$now
                        ]), $metadata);
                    } elseif ($lightningPayment->getState()->isFailed()) {
                        $this->then(FailPayment::fromNative([
                            'paymentId' => (string)$payment->getPaymentId(),
                            'revision' => (string)$payment->getRevision(),
                            'accountId' => (string)$payment->getAccountId(),
                            'amount' => (string)$payment->getAmount(),
                            'feeEstimate' => (string)$paymentTransaction->getFeeEstimate(),
                            'references' => $payment->getReferences()->toNative(),
                            'failedAt' => (string)$now
                        ]), $metadata);
                    }
                }
                break;
            case $service instanceof BitcoinServiceInterface:
                /** @var BitcoinServiceInterface $service */
                $transaction = $service->getTransaction($paymentTransaction->getId());
                if ($transaction
                    && $transaction->getConfirmations()->isGreaterThanOrEqualTo($paymentTransaction->getConfTarget())
                    && $transaction->getAmount()->isGreaterThanOrEqual($payment->getAmount())
                ) {
                    $this->then(CompletePayment::fromNative([
                        'paymentId' => (string)$payment->getPaymentId(),
                        'revision' => (string)$payment->getRevision(),
                        'accountId' => (string)$payment->getAccountId(),
                        'amount' => (string)$payment->getAmount(),
                        'feeRefund' => (string)$paymentTransaction->getFeeEstimate()->subtract(
                            $transaction->getFeeSettled()
                        ),
                        'references' => $payment->getReferences()->toNative(),
                        'completedAt' => (string)$now
                    ]), $metadata);
                } elseif (!$transaction) {
                    // fail unrecognised txs since they must have been dropped
                    $this->then(FailPayment::fromNative([
                        'paymentId' => (string)$payment->getPaymentId(),
                        'revision' => (string)$payment->getRevision(),
                        'accountId' => (string)$payment->getAccountId(),
                        'amount' => (string)$payment->getAmount(),
                        'feeEstimate' => (string)$paymentTransaction->getFeeEstimate(),
                        'references' => $payment->getReferences()->toNative(),
                        'failedAt' => (string)$now
                    ]), $metadata);
                }
                break;
        }
    }

    private function loadPayment(string $paymentId): Payment
    {
        /** @var Payment $payment */
        $payment = $this->paymentCollection->byId($paymentId)->getFirst();
        Assertion::isInstanceOf($payment, Payment::class);
        return $payment;
    }
}
