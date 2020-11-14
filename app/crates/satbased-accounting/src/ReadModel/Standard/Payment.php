<?php declare(strict_types=1);

namespace Satbased\Accounting\ReadModel\Standard;

use Daikon\Entity\Attribute;
use Daikon\Entity\AttributeMap;
use Daikon\Entity\Entity;
use Daikon\EventSourcing\Aggregate\AggregateRevision;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Daikon\ReadModel\Projection\EventHandlerTrait;
use Daikon\ReadModel\Projection\ProjectionInterface;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\TextList;
use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\Entity\ApprovalToken;
use Satbased\Accounting\Payment\Approve\PaymentApproved;
use Satbased\Accounting\Payment\Cancel\PaymentCancelled;
use Satbased\Accounting\Payment\Complete\PaymentCompleted;
use Satbased\Accounting\Payment\Fail\PaymentFailed;
use Satbased\Accounting\Payment\Make\PaymentMade;
use Satbased\Accounting\Payment\PaymentTrait;
use Satbased\Accounting\Payment\Receive\PaymentReceived;
use Satbased\Accounting\Payment\Settle\PaymentSettled;
use Satbased\Accounting\Payment\Request\PaymentRequested;
use Satbased\Accounting\Payment\Select\PaymentSelected;
use Satbased\Accounting\Payment\Send\PaymentSent;
use Satbased\Accounting\Payment\Token\ApprovalTokenAdded;
use Satbased\Accounting\Payment\Update\PaymentUpdated;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\PaymentDirection;
use Satbased\Accounting\ValueObject\PaymentId;
use Satbased\Accounting\ValueObject\PaymentState;
use Satbased\Accounting\ValueObject\PaymentTokenList;
use Satbased\Accounting\ValueObject\Transaction;
use Satbased\Security\ValueObject\ProfileId;

final class Payment extends Entity implements ProjectionInterface, ProprietaryInterface, ResourceInterface
{
    use PaymentTrait;
    use EventHandlerTrait;

    public static function getAttributeMap(): AttributeMap
    {
        return new AttributeMap([
            Attribute::define('paymentId', PaymentId::class),
            Attribute::define('revision', AggregateRevision::class),
            Attribute::define('profileId', ProfileId::class),
            Attribute::define('accountId', AccountId::class),
            Attribute::define('references', TextMap::class),
            Attribute::define('accepts', TextList::class),
            Attribute::define('amount', Bitcoin::class),
            Attribute::define('description', Text::class),
            Attribute::define('service', Text::class),
            Attribute::define('transaction', Transaction::class),
            Attribute::define('state', PaymentState::class),
            Attribute::define('direction', PaymentDirection::class),
            Attribute::define('tokens', PaymentTokenList::class),
            Attribute::define('requestedAt', Timestamp::class),
            Attribute::define('expiresAt', Timestamp::class),
            Attribute::define('selectedAt', Timestamp::class),
            Attribute::define('receivedAt', Timestamp::class),
            Attribute::define('settledAt', Timestamp::class),
            Attribute::define('completedAt', Timestamp::class),
            Attribute::define('cancelledAt', Timestamp::class),
            Attribute::define('approvedAt', Timestamp::class),
            Attribute::define('sentAt', Timestamp::class),
            Attribute::define('failedAt', Timestamp::class),
        ]);
    }

    public function getResourceId(): string
    {
        return self::class;
    }

    public function getOwnerId(): string
    {
        return (string)$this->getProfileId();
    }

    public function getIdentity(): PaymentId
    {
        return $this->getPaymentId();
    }

    public function adaptRevision(DomainEventInterface $event): self
    {
        return $this->withValue('revision', $event->getAggregateRevision());
    }

    protected function whenPaymentRequested(PaymentRequested $paymentRequested): self
    {
        return $this
            ->withValues($paymentRequested->toNative())
            ->withValue('state', PaymentState::REQUESTED)
            ->withValue('direction', PaymentDirection::INCOMING);
    }

    protected function whenPaymentMade(PaymentMade $paymentMade): self
    {
        $values = $paymentMade->toNative();
        unset($values['approvalToken']);
        unset($values['approvalTokenExpiresAt']);
        return $this
            ->withValues($values)
            ->withValue('state', PaymentState::MADE)
            ->withValue('direction', PaymentDirection::OUTGOING);
    }

    protected function whenApprovalTokenAdded(ApprovalTokenAdded $tokenAdded): self
    {
        $token = ApprovalToken::fromNative($tokenAdded->toNative());

        return $this
            ->adaptRevision($tokenAdded)
            ->withValue('tokens', $this->getTokens()->addToken($token));
    }

    protected function whenPaymentSelected(PaymentSelected $paymentSelected): self
    {
        return $this
            ->adaptRevision($paymentSelected)
            ->withValue('service', $paymentSelected->getService())
            ->withValue('transaction', $paymentSelected->getTransaction())
            ->withValue('selectedAt', $paymentSelected->getSelectedAt())
            ->withValue('state', PaymentState::SELECTED);
    }

    protected function whenPaymentUpdated(PaymentUpdated $paymentUpdated): self
    {
        return $this
            ->adaptRevision($paymentUpdated)
            ->withValue('references', $this->getReferences()->merge($paymentUpdated->getReferences()));
    }

    protected function whenPaymentReceived(PaymentReceived $paymentReceived): self
    {
        return $this
            ->adaptRevision($paymentReceived)
            ->withValue('receivedAt', $paymentReceived->getReceivedAt())
            ->withValue('state', PaymentState::RECEIVED);
    }

    protected function whenPaymentSettled(PaymentSettled $paymentSettled): self
    {
        return $this
            ->adaptRevision($paymentSettled)
            ->withValue('settledAt', $paymentSettled->getSettledAt())
            ->withValue('state', PaymentState::SETTLED);
    }

    protected function whenPaymentCompleted(PaymentCompleted $paymentCompleted): self
    {
        return $this
            ->adaptRevision($paymentCompleted)
            ->withValue('completedAt', $paymentCompleted->getCompletedAt())
            ->withValue('state', PaymentState::COMPLETED);
    }

    protected function whenPaymentCancelled(PaymentCancelled $paymentCancelled): self
    {
        return $this
            ->adaptRevision($paymentCancelled)
            ->withValue('cancelledAt', $paymentCancelled->getCancelledAt())
            ->withValue('state', PaymentState::CANCELLED);
    }

    protected function whenPaymentApproved(PaymentApproved $paymentApproved): self
    {
        return $this
            ->adaptRevision($paymentApproved)
            ->withValue('approvedAt', $paymentApproved->getApprovedAt())
            ->withValue('state', PaymentState::APPROVED);
    }

    protected function whenPaymentSent(PaymentSent $paymentSent): self
    {
        return $this
            ->adaptRevision($paymentSent)
            ->withValue('transaction', $paymentSent->getTransaction())
            ->withValue('sentAt', $paymentSent->getSentAt())
            ->withValue('state', PaymentState::SENT);
    }

    protected function whenPaymentFailed(PaymentFailed $paymentFailed): self
    {
        return $this
            ->adaptRevision($paymentFailed)
            ->withValue('failedAt', $paymentFailed->getFailedAt())
            ->withValue('state', PaymentState::FAILED);
    }
}
