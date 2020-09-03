<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Token;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Satbased\Security\Profile\Close\CloseProfile;
use Satbased\Security\Profile\ProfileMessageTrait;
use Satbased\Security\Profile\Verify\VerifyProfile;
use Satbased\Security\ValueObject\ProfileId;

final class VerificationTokenRemoved implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use ProfileMessageTrait;

    private static function fromCommand(ProfileId $profileId): self
    {
        return self::fromNative(['profileId' => (string)$profileId]);
    }

    public static function fromVerify(VerifyProfile $verifyProfile): self
    {
        return self::fromCommand($verifyProfile->getProfileId());
    }

    public static function fromClose(CloseProfile $closeProfile): self
    {
        return self::fromCommand($closeProfile->getProfileId());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return false;
    }
}
