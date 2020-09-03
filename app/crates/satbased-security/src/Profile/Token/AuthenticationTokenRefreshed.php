<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Token;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Daikon\ValueObject\Timestamp;
use Satbased\Security\Profile\ProfileMessageTrait;
use Satbased\Security\Profile\Login\LoginProfile;

/**
 * @map(expiresAt, Daikon\ValueObject\Timestamp)
 */
final class AuthenticationTokenRefreshed implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use ProfileMessageTrait;

    private Timestamp $expiresAt;

    public static function fromCommand(LoginProfile $loginProfile): self
    {
        return self::fromNative([
            'profileId' => (string)$loginProfile->getProfileId(),
            'expiresAt' => (string)$loginProfile->getAuthenticationTokenExpiresAt()
        ]);
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }

    public function getExpiresAt(): Timestamp
    {
        return $this->expiresAt;
    }
}
