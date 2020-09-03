<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Token;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Daikon\ValueObject\Sha256;
use Daikon\ValueObject\Timestamp;
use Daikon\ValueObject\Uuid;
use Satbased\Security\Profile\ProfileMessageTrait;
use Satbased\Security\Profile\Close\CloseProfile;
use Satbased\Security\Profile\Logout\LogoutProfile;
use Satbased\Security\ValueObject\ProfileId;

/**
 * @map(id, Daikon\ValueObject\Uuid)
 * @map(token, Daikon\ValueObject\Sha256)
 * @map(expiresAt, Daikon\ValueObject\Timestamp)
 */
final class AuthenticationTokenExpired implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use ProfileMessageTrait;

    private Uuid $id;

    private Sha256 $token;

    private Timestamp $expiresAt;

    private static function fromCommand(ProfileId $profileId): self
    {
        return self::fromNative([
            'profileId' => (string)$profileId,
            'id' => (string)Uuid::generate(),
            'token' => (string)Sha256::generate(),
            'expiresAt' => (string)Timestamp::epoch()
        ]);
    }

    public static function fromLogout(LogoutProfile $logoutProfile): self
    {
        return self::fromCommand($logoutProfile->getProfileId());
    }

    public static function fromClose(CloseProfile $closeProfile): self
    {
        return self::fromCommand($closeProfile->getProfileId());
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
