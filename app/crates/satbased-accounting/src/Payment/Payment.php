<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment;

use Daikon\EventSourcing\Aggregate\AggregateRoot;
use Daikon\Interop\Assertion;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\TextList;
use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Entity\ApprovalToken;
use Satbased\Accounting\Payment\Approve\ApprovePayment;
use Satbased\Accounting\Payment\Approve\PaymentApproved;
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
use Satbased\Accounting\Payment\Token\ApprovalTokenAdded;
use Satbased\Accounting\Payment\Update\PaymentUpdated;
use Satbased\Accounting\Payment\Update\UpdatePayment;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\PaymentDirection;
use Satbased\Accounting\ValueObject\PaymentState;
use Satbased\Accounting\ValueObject\PaymentTokenList;
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

    private Timestamp $approvedAt;

    private Timestamp $sentAt;

    private Timestamp $failedAt;

    private PaymentState $state;

    private PaymentDirection $direction;

    private PaymentTokenList $tokens;

    public static function request(RequestPayment $requestPayment): self
    {
        return (new self($requestPayment->getPaymentId()))
            ->reflectThat(PaymentRequested::fromCommand($requestPayment));
    }

    public static function make(MakePayment $makePayment): self
    {
        return (new self($makePayment->getPaymentId()))
            ->reflectThat(PaymentMade::fromCommand($makePayment))
            ->reflectThat(ApprovalTokenAdded::fromCommand($makePayment));
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

    public function update(UpdatePayment $updatePayment): self
    {
        Assertion::true($this->canBeUpdated($updatePayment->getUpdatedAt()), 'Payment cannot be updated.');

        return $this->reflectThat(PaymentUpdated::fromCommand($updatePayment));
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
        Assertion::true(
            $settlePayment->getAmount()->isGreaterThanOrEqual($this->amount),
            'Payment settled amount is less than expected.'
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

    public function approve(ApprovePayment $approvePayment): self
    {
        Assertion::true($this->canBeApproved($approvePayment->getApprovedAt()), 'Payment cannot be approved.');
        Assertion::true(
            $this->getTokens()->getApprovalToken()->approve(
                $approvePayment->getToken(),
                $approvePayment->getApprovedAt()
            ),
            'Token is not approved.'
        );

        return $this->reflectThat(PaymentApproved::fromCommand($approvePayment));
    }

    public function send(SendPayment $sendPayment): self
    {
        Assertion::true($this->canBeSent(), 'Payment cannot be sent.');

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
        $this->tokens = PaymentTokenList::makeEmpty();
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
        $this->approvedAt = Timestamp::makeEmpty();
        $this->sentAt = Timestamp::makeEmpty();
        $this->failedAt = Timestamp::makeEmpty();
        $this->state = PaymentState::fromNative(PaymentState::MADE);
        $this->direction = PaymentDirection::fromNative(PaymentDirection::OUTGOING);
        $this->tokens = PaymentTokenList::makeEmpty();
    }

    protected function whenApprovalTokenAdded(ApprovalTokenAdded $tokenAdded): void
    {
        $this->tokens = $this->tokens->addToken(ApprovalToken::fromNative($tokenAdded->toNative()));
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

    protected function whenPaymentUpdated(PaymentUpdated $paymentUpdated): void
    {
        $this->references = $this->references->merge($paymentUpdated->getReferences());
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

    protected function whenPaymentApproved(PaymentApproved $paymentApproved): void
    {
        $this->approvedAt = $paymentApproved->getApprovedAt();
        $this->state = PaymentState::fromNative(PaymentState::APPROVED);
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
