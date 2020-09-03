<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment;

use Daikon\EventSourcing\Aggregate\AggregateRoot;
use Daikon\Interop\Assertion;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\TextList;
use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Payment\Cancel\CancelPayment;
use Satbased\Accounting\Payment\Cancel\PaymentCancelled;
use Satbased\Accounting\Payment\Complete\CompletePayment;
use Satbased\Accounting\Payment\Complete\PaymentCompleted;
use Satbased\Accounting\Payment\Fail\FailPayment;
use Satbased\Accounting\Payment\Fail\PaymentFailed;
use Satbased\Accounting\Payment\Make\MakePayment;
use Satbased\Accounting\Payment\Make\PaymentMade;
use Satbased\Accounting\Payment\Receive\ReceivePayment;
use Satbased\Accounting\Payment\Receive\PaymentReceived;
use Satbased\Accounting\Payment\Request\PaymentRequested;
use Satbased\Accounting\Payment\Request\RequestPayment;
use Satbased\Accounting\Payment\Select\PaymentSelected;
use Satbased\Accounting\Payment\Select\SelectPayment;
use Satbased\Accounting\Payment\Send\PaymentSent;
use Satbased\Accounting\Payment\Send\SendPayment;
use Satbased\Accounting\Payment\Settle\PaymentSettled;
use Satbased\Accounting\Payment\Settle\SettlePayment;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\PaymentDirection;
use Satbased\Accounting\ValueObject\PaymentState;
use Satbased\Accounting\ValueObject\Transaction;
use Satbased\Security\ValueObject\ProfileId;

final class Payment extends AggregateRoot
{
    use PaymentTrait;

    private ProfileId $profileId;

    private AccountId $accountId;

    private TextMap $references;

    private ?TextList $accepts;

    private Bitcoin $amount;

    private Text $description;

    private Text $service;

    private ?Transaction $transaction;

    private Timestamp $expiresAt;

    //@todo rename to createdAt or similar
    private Timestamp $requestedAt;

    private Timestamp $selectedAt;

    private Timestamp $receivedAt;

    private Timestamp $settledAt;

    private Timestamp $completedAt;

    private Timestamp $cancelledAt;

    private Timestamp $sentAt;

    private Timestamp $failedAt;

    private PaymentState $state;

    private PaymentDirection $direction;

    public static function request(RequestPayment $requestPayment): self
    {
        return (new self($requestPayment->getPaymentId()))
            ->reflectThat(PaymentRequested::fromCommand($requestPayment));
    }

    public static function make(MakePayment $makePayment): self
    {
        return (new self($makePayment->getPaymentId()))
            ->reflectThat(PaymentMade::fromCommand($makePayment));
    }

    public function select(SelectPayment $selectPayment): self
    {
        Assertion::true($this->canBeSelected($selectPayment->getSelectedAt()), 'Payment cannot be selected.');
        Assertion::true(
            $this->accepts->isEmpty() || $this->accepts->find($selectPayment->getService()) !== false,
            'Selected service is unacceptable.'
        );

        return $this->reflectThat(PaymentSelected::fromCommand($selectPayment));
    }

    public function receive(ReceivePayment $receivePayment): self
    {
        return $this->reflectThat(PaymentReceived::fromCommand($receivePayment));
    }

    public function settle(SettlePayment $settlePayment): self
    {
        Assertion::true(
            $this->canBeSettled($settlePayment->getSettledAt()),
            'Payment cannot be settled.'
        );

        return $this->reflectThat(PaymentSettled::fromCommand($settlePayment));
    }

    public function complete(CompletePayment $completePayment): self
    {
        Assertion::true(
            $this->canBeCompleted($completePayment->getCompletedAt()),
            'Payment cannot be completed.'
        );

        return $this->reflectThat(PaymentCompleted::fromCommand($completePayment));
    }

    public function cancel(CancelPayment $cancelPayment): self
    {
        Assertion::true(
            $this->canBeCancelled($cancelPayment->getCancelledAt()),
            'Payment cannot be cancelled.'
        );

        return $this->reflectThat(PaymentCancelled::fromCommand($cancelPayment));
    }

    public function send(SendPayment $sendPayment): self
    {
        Assertion::true($this->canBeSent($sendPayment->getSentAt()), 'Payment cannot be sent.');

        return $this->reflectThat(PaymentSent::fromCommand($sendPayment));
    }

    public function fail(FailPayment $failPayment): self
    {
        Assertion::true($this->canBeFailed(), 'Payment cannot be failed.');

        return $this->reflectThat(PaymentFailed::fromCommand($failPayment));
    }

    protected function whenPaymentRequested(PaymentRequested $paymentRequested): void
    {
        $this->accountId = $paymentRequested->getAccountId();
        $this->references = $paymentRequested->getReferences();
        $this->accepts = $paymentRequested->getAccepts();
        $this->amount = $paymentRequested->getAmount();
        $this->description = $paymentRequested->getDescription();
        $this->service = Text::makeEmpty();
        $this->transaction = null;
        $this->expiresAt = $paymentRequested->getExpiresAt();
        $this->requestedAt = $paymentRequested->getRequestedAt();
        $this->selectedAt = Timestamp::makeEmpty();
        $this->receivedAt = Timestamp::makeEmpty();
        $this->settledAt = Timestamp::makeEmpty();
        $this->cancelledAt = Timestamp::makeEmpty();
        $this->state = PaymentState::fromNative(PaymentState::REQUESTED);
        $this->direction = PaymentDirection::fromNative(PaymentDirection::INCOMING);
    }

    protected function whenPaymentMade(PaymentMade $paymentMade): void
    {
        $this->accountId = $paymentMade->getAccountId();
        $this->references = $paymentMade->getReferences();
        $this->accepts = null;
        $this->amount = $paymentMade->getAmount();
        $this->description = $paymentMade->getDescription();
        $this->service = $paymentMade->getService();
        $this->transaction = $paymentMade->getTransaction();
        $this->expiresAt = Timestamp::makeEmpty();
        $this->requestedAt = $paymentMade->getRequestedAt();
        $this->completedAt = Timestamp::makeEmpty();
        $this->cancelledAt = Timestamp::makeEmpty();
        $this->sentAt = Timestamp::makeEmpty();
        $this->failedAt = Timestamp::makeEmpty();
        $this->state = PaymentState::fromNative(PaymentState::MADE);
        $this->direction = PaymentDirection::fromNative(PaymentDirection::OUTGOING);
    }

    protected function whenPaymentSelected(PaymentSelected $paymentSelected): void
    {
        $this->service = $paymentSelected->getService();
        $this->transaction = $paymentSelected->getTransaction();
        $this->selectedAt = $paymentSelected->getSelectedAt();
        $this->state = PaymentState::fromNative(PaymentState::SELECTED);
    }

    protected function whenPaymentReceived(PaymentReceived $paymentReceived): void
    {
        $this->receivedAt = $paymentReceived->getReceivedAt();
        $this->state = PaymentState::fromNative(PaymentState::RECEIVED);
    }

    protected function whenPaymentSettled(PaymentSettled $paymentSettled): void
    {
        $this->settledAt = $paymentSettled->getSettledAt();
        $this->state = PaymentState::fromNative(PaymentState::SETTLED);
    }

    protected function whenPaymentCompleted(PaymentCompleted $paymentCompleted): void
    {
        $this->completedAt = $paymentCompleted->getCompletedAt();
        $this->state = PaymentState::fromNative(PaymentState::COMPLETED);
    }

    protected function whenPaymentCancelled(PaymentCancelled $paymentCancelled): void
    {
        $this->cancelledAt = $paymentCancelled->getCancelledAt();
        $this->state = PaymentState::fromNative(PaymentState::CANCELLED);
    }

    protected function whenPaymentSent(PaymentSent $paymentSent): void
    {
        $this->transaction = $paymentSent->getTransaction();
        $this->sentAt = $paymentSent->getSentAt();
        $this->state = PaymentState::fromNative(PaymentState::SENT);
    }

    protected function whenPaymentFailed(PaymentFailed $paymentFailed): void
    {
        $this->failedAt = $paymentFailed->getFailedAt();
        $this->state = PaymentState::fromNative(PaymentState::FAILED);
    }
}
