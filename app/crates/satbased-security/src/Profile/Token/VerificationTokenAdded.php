<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Token;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Daikon\ValueObject\Sha256;
use Daikon\ValueObject\Timestamp;
use Daikon\ValueObject\Uuid;
use Satbased\Security\Profile\ProfileMessageTrait;
use Satbased\Security\Profile\Register\RegisterProfile;

/**
 * @map(id, Daikon\ValueObject\Uuid)
 * @map(token, Daikon\ValueObject\Sha256)
 * @map(expiresAt, Daikon\ValueObject\Timestamp)
 */
final class VerificationTokenAdded implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use ProfileMessageTrait;

    private Uuid $id;

    private Sha256 $token;

    private Timestamp $expiresAt;

    public static function fromCommand(RegisterProfile $registerProfile): self
    {
        return self::fromNative([
            'profileId' => (string)$registerProfile->getProfileId(),
            'id' => (string)Uuid::generate(),
            'token' => (string)Sha256::generate(),
            'expiresAt' => (string)$registerProfile->getVerificationTokenExpiresAt()
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
