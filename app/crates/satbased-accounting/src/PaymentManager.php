<?php declare(strict_types=1);

namespace Satbased\Accounting;

use Daikon\AsyncJob\Job\JobDefinitionInterface;
use Daikon\AsyncJob\Job\JobDefinitionMap;
use Daikon\AsyncJob\Metadata\JobMetadataEnricher;
use Daikon\Boot\Service\ProcessManagerTrait;
use Daikon\Elasticsearch7\Query\TermFilter;
use Daikon\Elasticsearch7\Query\TermsFilter;
use Daikon\Interop\Assertion;
use Daikon\MessageBus\Channel\Subscription\MessageHandler\MessageHandlerInterface;
use Daikon\MessageBus\Envelope;
use Daikon\MessageBus\MessageBusInterface;
use Daikon\Metadata\MetadataInterface;
use Daikon\Money\Exception\PaymentServiceException;
use Daikon\Money\Exception\PaymentServiceUnavailable;
use Daikon\Money\Service\PaymentServiceMap;
use Daikon\ValueObject\Natural;
use Daikon\ValueObject\Sha256;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\Entity\BitcoinBlock;
use NGUtech\Bitcoin\Entity\BitcoinTransaction;
use NGUtech\Bitcoin\Message\BitcoinBlockHashReceived;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use NGUtech\Bitcoind\Service\BitcoindService;
use NGUtech\Lightning\Entity\LightningInvoice;
use NGUtech\Lightning\Message\LightningInvoiceMessageInterface;
use NGUtech\Lightning\Service\LightningHoldServiceInterface;
use NGUtech\Lightningd\Message\LightningdInvoiceSettled;
use NGUtech\Lightningd\Message\LightningdPaymentSucceeded;
use NGUtech\Lnd\Message\LndInvoiceAccepted;
use NGUtech\Lnd\Message\LndInvoiceCancelled;
use NGUtech\Lnd\Message\LndInvoiceSettled;
use Psr\Log\LoggerInterface;
use Satbased\Accounting\Entity\BalanceTransfer;
use Satbased\Accounting\Payment\Approve\ApprovePayment;
use Satbased\Accounting\Payment\Approve\PaymentApproved;
use Satbased\Accounting\Payment\Cancel\CancelPayment;
use Satbased\Accounting\Payment\Complete\CompletePayment;
use Satbased\Accounting\Payment\Fail\FailPayment;
use Satbased\Accounting\Payment\Make\MakePayment;
use Satbased\Accounting\Payment\Make\PaymentMade;
use Satbased\Accounting\Payment\Receive\ReceivePayment;
use Satbased\Accounting\Payment\Select\PaymentSelected;
use Satbased\Accounting\Payment\Send\PaymentSent;
use Satbased\Accounting\Payment\Send\SendPayment;
use Satbased\Accounting\Payment\Settle\SettlePayment;
use Satbased\Accounting\ReadModel\Standard\Payment;
use Satbased\Accounting\ReadModel\Standard\PaymentCollection;
use Satbased\Accounting\ValueObject\PaymentState;

final class PaymentManager implements MessageHandlerInterface
{
    use ProcessManagerTrait;

    private LoggerInterface $logger;

    private MessageBusInterface $messageBus;

    private PaymentCollection $paymentCollection;

    private PaymentServiceMap $paymentServiceMap;

    private BitcoindService $bitcoindService;

    private JobDefinitionMap $jobDefinitionMap;

    public function __construct(
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        PaymentCollection $paymentCollection,
        PaymentServiceMap $paymentServiceMap,
        BitcoindService $bitcoindService,
        JobDefinitionMap $jobDefinitionMap
    ) {
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->paymentCollection = $paymentCollection;
        $this->paymentServiceMap = $paymentServiceMap;
        $this->bitcoindService = $bitcoindService;
        $this->jobDefinitionMap = $jobDefinitionMap;
    }

    protected function whenBitcoinBlockHashReceived(
        BitcoinBlockHashReceived $bitcoinBlockHashReceived,
        MetadataInterface $metadata
    ): void {
        $block = $this->bitcoindService->getBlock($bitcoinBlockHashReceived->getHash());
        $this->handleLightningInvoiceTimeouts($block);
        $this->handleBitcoinReceived($block, $metadata);
        $this->handleBitcoinSent($block, $metadata);
    }

    private function handleLightningInvoiceTimeouts(BitcoinBlock $block): void
    {
        $this->paymentCollection->walk(
            TermsFilter::fromNative([
                'state' => PaymentState::RECEIVED,
                'transaction.@type' => LightningInvoice::class
            ]),
            function (Payment $receivedPayment) use ($block) {
                /** @var LightningInvoice $lightningInvoice */
                $lightningInvoice = $receivedPayment->getTransaction()->unwrap();
                $expiryHeight = $lightningInvoice->getExpiryHeight();
                if ($expiryHeight->subtract($block->getHeight())->isLessThanOrEqualTo(Natural::fromNative(2))) {
                    /** @var LightningHoldServiceInterface $lightningHoldService */
                    $lightningHoldService = $this->paymentServiceMap->get((string)$receivedPayment->getService());
                    $lightningHoldService->cancel($lightningInvoice);
                }
            }
        );
    }

    private function handleBitcoinReceived(BitcoinBlock $block, MetadataInterface $metadata): void
    {
        $this->paymentCollection->walk(
            TermsFilter::fromNative([
                'state' => PaymentState::SELECTED,
                'transaction.@type' => BitcoinTransaction::class
                //@todo timeout normal expectations for incomplete txs
            ]),
            function (Payment $payment) use ($block, $metadata) {
                /** @var BitcoinTransaction $bitcoinTransaction */
                $bitcoinTransaction = $payment->getTransaction()->unwrap();
                $address = $bitcoinTransaction->getOutputs()->first()->getAddress(); //Assume one address to check
                $confirmedBalance = $this->bitcoindService->getConfirmedBalance(
                    $address,
                    $bitcoinTransaction->getConfTarget()
                );
                if ($confirmedBalance->isGreaterThanOrEqual($payment->getAmount())) {
                    $this->then(SettlePayment::fromNative([
                        'paymentId' => (string)$payment->getPaymentId(),
                        'revision' => (string)$payment->getRevision(),
                        'profileId' => (string)$payment->getProfileId(),
                        'accountId' => (string)$payment->getAccountId(),
                        'amount' => (string)$confirmedBalance,
                        'references' => $payment->getReferences()->toNative(),
                        'settledAt' => (string)$block->getTimestamp()
                    ]), $metadata);
                }
            }
        );
    }

    private function handleBitcoinSent(BitcoinBlock $block, MetadataInterface $metadata): void
    {
        $this->paymentCollection->walk(
            TermsFilter::fromNative([
                'state' => PaymentState::SENT,
                'transaction.@type' => BitcoinTransaction::class
            ]),
            function (Payment $payment) use ($block, $metadata) {
                /** @var BitcoinTransaction $bitcoinTransaction */
                $bitcoinTransaction = $payment->getTransaction()->unwrap();
                $transaction = $this->bitcoindService->getTransaction($bitcoinTransaction->getId());
                if ($transaction
                    && $transaction->getConfirmations()->isGreaterThanOrEqualTo($bitcoinTransaction->getConfTarget())
                    && $transaction->getAmount()->isGreaterThanOrEqual($payment->getAmount())
                ) {
                    $this->then(CompletePayment::fromNative([
                        'paymentId' => (string)$payment->getPaymentId(),
                        'revision' => (string)$payment->getRevision(),
                        'accountId' => (string)$payment->getAccountId(),
                        'amount' => (string)$payment->getAmount(),
                        'feeRefund' => (string)$payment->getFeeRefund(),
                        'references' => $payment->getReferences()->toNative(),
                        'completedAt' => (string)$block->getTimestamp()
                    ]), $metadata);
                }
            }
        );
    }

    protected function whenLndInvoiceAccepted(
        LndInvoiceAccepted $lndInvoiceAccepted,
        MetadataInterface $metadata
    ): void {
        /** @var Payment $payment */
        $payment = $this->paymentCollection->selectOne(
            TermFilter::fromNative([
                'transaction.preimageHash' => (string)$lndInvoiceAccepted->getPreimageHash()
            ])
        )->getFirst();

        $receivedAt = $lndInvoiceAccepted->getTimestamp();
        if ($payment instanceof Payment && $payment->canBeReceived($receivedAt)) {
            $this->then(ReceivePayment::fromNative([
                'paymentId' => (string)$payment->getPaymentId(),
                'revision' => (string)$payment->getRevision(),
                'accountId' => (string)$payment->getAccountId(),
                'amount' => (string)$lndInvoiceAccepted->getAmountPaid(),
                'references' => $payment->getReferences()->toNative(),
                'receivedAt' => (string)$receivedAt
            ]), $metadata);
        }
    }

    protected function whenLndInvoiceCancelled(
        LndInvoiceCancelled $lndInvoiceCancelled,
        MetadataInterface $metadata
    ): void {
        /** @var Payment $payment */
        $payment = $this->paymentCollection->selectOne(
            TermFilter::fromNative([
                'transaction.preimageHash' => (string)$lndInvoiceCancelled->getPreimageHash()
            ])
        )->getFirst();

        $cancelledAt = $lndInvoiceCancelled->getTimestamp();
        if ($payment instanceof Payment && $payment->canBeCancelled($cancelledAt)) {
            $this->then(CancelPayment::fromNative([
                'paymentId' => (string)$payment->getPaymentId(),
                'revision' => (string)$payment->getRevision(),
                'accountId' => (string)$payment->getAccountId(),
                'amount' => (string)$payment->getAmount(),
                'feeEstimate' => (string)Bitcoin::zero(),
                'references' => $payment->getReferences()->toNative(),
                'state' => (string)$payment->getState(),
                'cancelledAt' => (string)$cancelledAt
            ]), $metadata);
        }
    }

    protected function whenLndInvoiceSettled(
        LndInvoiceSettled $lndInvoiceSettled,
        MetadataInterface $metadata
    ): void {
        $this->handleLightningInvoiceSettled($lndInvoiceSettled, $metadata);
    }

    protected function whenLightningdInvoiceSettled(
        LightningdInvoiceSettled $lightningdInvoiceSettled,
        MetadataInterface $metadata
    ): void {
        $this->handleLightningInvoiceSettled($lightningdInvoiceSettled, $metadata);
    }

    private function handleLightningInvoiceSettled(
        LightningInvoiceMessageInterface $invoiceSettled,
        MetadataInterface $metadata
    ): void {
        Assertion::true(
            $invoiceSettled instanceof LndInvoiceSettled || $invoiceSettled instanceof LightningdInvoiceSettled
        );

        /** @var Payment $payment */
        $payment = $this->paymentCollection->selectOne(
            TermFilter::fromNative([
                'transaction.preimageHash' => (string)$invoiceSettled->getPreimageHash()
            ])
        )->getFirst();

        $settledAt = $invoiceSettled->getTimestamp();
        if ($payment instanceof Payment && $payment->canBeSettled($settledAt)) {
            $this->then(SettlePayment::fromNative([
                'paymentId' => (string)$payment->getPaymentId(),
                'revision' => (string)$payment->getRevision(),
                'profileId' => (string)$payment->getProfileId(),
                'accountId' => (string)$payment->getAccountId(),
                'amount' => (string)$invoiceSettled->getAmountPaid(),
                'references' => $payment->getReferences()->toNative(),
                'settledAt' => (string)$settledAt
            ]), $metadata);
        }
    }

    protected function whenLightningdPaymentSucceeded(
        LightningdPaymentSucceeded $lightningdPaymentSucceeded,
        MetadataInterface $metadata
    ): void {
        /** @var Payment $payment */
        $payment = $this->paymentCollection->selectOne(
            TermFilter::fromNative([
                'transaction.preimageHash' => (string)$lightningdPaymentSucceeded->getPreimageHash()
            ])
        )->getFirst();

        $completedAt = $lightningdPaymentSucceeded->getTimestamp();
        if ($payment instanceof Payment && $payment->canBeCompleted($completedAt)) {
            $this->then(CompletePayment::fromNative([
                'paymentId' => (string)$payment->getPaymentId(),
                'revision' => (string)$payment->getRevision(),
                'accountId' => (string)$payment->getAccountId(),
                'amount' => (string)$payment->getAmount(),
                'feeRefund' => (string)$payment->getFeeRefund(),
                'references' => $payment->getReferences()->toNative(),
                'completedAt' => (string)$completedAt
            ]), $metadata);
        }
    }

    protected function whenPaymentSelected(PaymentSelected $paymentSelected, MetadataInterface $metadata): void
    {
        $transaction = $paymentSelected->getTransaction()->unwrap();
        if ($transaction instanceof BalanceTransfer) {
            //Pay balance transfer payments immediately
            $payment = $this->loadPayment((string)$paymentSelected->getPaymentId());
            $selectedAt = $paymentSelected->getSelectedAt();
            $this->then(MakePayment::fromNative([
                'paymentId' => (string)$transaction->getPaymentId(),
                'profileId' => (string)$transaction->getProfileId(),
                'accountId' => (string)$transaction->getAccountId(),
                'amount' => (string)$transaction->getAmount(),
                'description' => (string)$payment->getDescription(),
                'service' => (string)$paymentSelected->getService(),
                'transaction' => BalanceTransfer::fromNative([
                    'id' => (string)$transaction->getIdentity(),
                    'paymentId' => (string)$payment->getPaymentId(),
                    'profileId' => (string)$payment->getProfileId(),
                    'accountId' => (string)$payment->getAccountId(),
                    'amount' => (string)$transaction->getAmount(),
                ])->toNative(),
                'requestedAt' => (string)$selectedAt,
                'approvalToken' => (string)Sha256::generate(),
                'approvalTokenExpiresAt' => (string)$selectedAt->modify('+1 hour')
            ]), $metadata);
        }
    }

    protected function whenPaymentMade(PaymentMade $paymentMade, MetadataInterface $metadata): void
    {
        $transaction = $paymentMade->getTransaction()->unwrap();
        if ($transaction instanceof BalanceTransfer) {
            $payment = $this->loadPayment((string)$paymentMade->getPaymentId());
            $this->then(ApprovePayment::fromNative([
                'paymentId' => (string)$payment->getPaymentId(),
                'revision' => (string)$payment->getRevision(),
                'approvedAt' => (string)$paymentMade->getRequestedAt(),
                'token' => (string)$payment->getTokens()->getApprovalToken()->getToken()
            ]), $metadata);
        }
    }

    protected function whenPaymentApproved(PaymentApproved $paymentApproved, MetadataInterface $metadata): void
    {
        $payment = $this->loadPayment((string)$paymentApproved->getPaymentId());
        if ($payment->canBeSent()) {
            $now = Timestamp::now();
            try {
                $paymentService = $this->paymentServiceMap->get((string)$payment->getService());
                $transaction = $paymentService->send($payment->getTransaction()->unwrap());
                //With lightning payments it is possible for the sending success notification to be
                //received before the payment AR is updated with the transaction preimage so completion fails.
                //This is mitigated by using delayed notification lightning message relaying.
                $this->then(SendPayment::fromNative([
                    'paymentId' => (string)$payment->getPaymentId(),
                    'revision' => (string)$payment->getRevision(),
                    'accountId' => (string)$payment->getAccountId(),
                    'transaction' => $transaction->toNative(),
                    'references' => $payment->getReferences()->toNative(),
                    'sentAt' => (string)$now
                ]), $metadata);
            } catch (PaymentServiceException $error) {
                //Fail if this is a job that cannot retry. Bit hacky, maybe a better way to handle, possibly
                //with a custom worker, or with some additional features on the process manager.
                if ($error instanceof PaymentServiceUnavailable && $metadata->has(JobMetadataEnricher::JOB)) {
                    /** @var JobDefinitionInterface $jobDefinition */
                    $jobDefinition = $this->jobDefinitionMap->get($metadata->get(JobMetadataEnricher::JOB));
                    if ($jobDefinition->getStrategy()->canRetry(Envelope::wrap($paymentApproved, $metadata))) {
                        throw $error;
                    }
                }
                $this->then(FailPayment::fromNative([
                    'paymentId' => (string)$payment->getPaymentId(),
                    'revision' => (string)$payment->getRevision(),
                    'accountId' => (string)$payment->getAccountId(),
                    'amount' => (string)$payment->getAmount(),
                    'feeEstimate' => (string)$payment->getFeeEstimate(),
                    'references' => $payment->getReferences()->toNative(),
                    'failedAt' => (string)$now
                ]), $metadata);
                throw $error;
            }
        }
    }

    protected function whenPaymentSent(PaymentSent $paymentSent, MetadataInterface $metadata): void
    {
        $transaction = $paymentSent->getTransaction()->unwrap();
        if ($transaction instanceof BalanceTransfer) {
            $this->then(CompletePayment::fromNative([
                'paymentId' => (string)$paymentSent->getPaymentId(),
                'revision' => (string)$paymentSent->getAggregateRevision(),
                'accountId' => (string)$paymentSent->getAccountId(),
                'amount' => (string)$transaction->getAmount(),
                'feeRefund' => (string)Bitcoin::zero(),
                'references' => $paymentSent->getReferences()->toNative(),
                'completedAt' => (string)$paymentSent->getSentAt()
            ]), $metadata);
            $receiverPayment = $this->loadPayment((string)$transaction->getPaymentId());
            $this->then(SettlePayment::fromNative([
                'paymentId' => (string)$receiverPayment->getPaymentId(),
                'revision' => (string)$receiverPayment->getRevision(),
                'profileId' => (string)$receiverPayment->getProfileId(),
                'accountId' => (string)$receiverPayment->getAccountId(),
                'amount' => (string)$receiverPayment->getAmount(),
                'references' => $receiverPayment->getReferences()->toNative(),
                'settledAt' => (string)$paymentSent->getSentAt()
            ]), $metadata);
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
