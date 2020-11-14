<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Token;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Daikon\ValueObject\Sha256;
use Daikon\ValueObject\Timestamp;
use Daikon\ValueObject\Uuid;
use Satbased\Accounting\Payment\Make\MakePayment;
use Satbased\Accounting\Payment\PaymentMessageTrait;

/**
 * @map(id, Daikon\ValueObject\Uuid)
 * @map(token, Daikon\ValueObject\Sha256)
 * @map(expiresAt, Daikon\ValueObject\Timestamp)
 */
final class ApprovalTokenAdded implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use PaymentMessageTrait;

    private Uuid $id;

    private Sha256 $token;

    private Timestamp $expiresAt;

    public static function fromCommand(MakePayment $makePayment): self
    {
        return self::fromNative([
            'paymentId' => (string)$makePayment->getPaymentId(),
            'id' => (string)Uuid::generate(),
            'token' => (string)$makePayment->getApprovalToken(),
            'expiresAt' => (string)$makePayment->getApprovalTokenExpiresAt()
        ]);
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getToken(): Sha256
    {
        return $this->token;
    }

    public function getExpiresAt(): Timestamp
    {
        return $this->expiresAt;
    }
}
