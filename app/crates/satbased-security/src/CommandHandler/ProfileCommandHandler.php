<?php declare(strict_types=1);

namespace Satbased\Security\CommandHandler;

use Daikon\EventSourcing\Aggregate\Command\CommandHandler;
use Daikon\Metadata\MetadataInterface;
use Satbased\Security\Profile\Profile;
use Satbased\Security\Profile\Close\CloseProfile;
use Satbased\Security\Profile\Login\LoginProfile;
use Satbased\Security\Profile\Logout\LogoutProfile;
use Satbased\Security\Profile\Promote\PromoteProfile;
use Satbased\Security\Profile\Register\RegisterProfile;
use Satbased\Security\Profile\Verify\VerifyProfile;

final class ProfileCommandHandler extends CommandHandler
{
    protected function handleRegisterProfile(RegisterProfile $registerProfile, MetadataInterface $metadata): array
    {
        return [Profile::register($registerProfile), $metadata];
    }

    protected function handleVerifyProfile(VerifyProfile $verifyProfile, MetadataInterface $metadata): array
    {
        /** @var Profile $profile */
        $profile = $this->checkout($verifyProfile->getProfileId(), $verifyProfile->getKnownAggregateRevision());
        return [$profile->verify($verifyProfile), $metadata];
    }

    protected function handleLoginProfile(LoginProfile $loginProfile, MetadataInterface $metadata): array
    {
        /** @var Profile $profile */
        $profile = $this->checkout($loginProfile->getProfileId(), $loginProfile->getKnownAggregateRevision());
        return [$profile->login($loginProfile), $metadata];
    }

    protected function handleLogoutProfile(LogoutProfile $logoutProfile, MetadataInterface $metadata): array
    {
        /** @var Profile $profile */
        $profile = $this->checkout($logoutProfile->getProfileId(), $logoutProfile->getKnownAggregateRevision());
        return [$profile->logout($logoutProfile), $metadata];
    }

    protected function handleCloseProfile(CloseProfile $closeProfile, MetadataInterface $metadata): array
    {
        /** @var Profile $profile */
        $profile = $this->checkout($closeProfile->getProfileId(), $closeProfile->getKnownAggregateRevision());
        return [$profile->close($closeProfile), $metadata];
    }

    protected function handlePromoteProfile(PromoteProfile $promoteProfile, MetadataInterface $metadata): array
    {
        /** @var Profile $profile */
        $profile = $this->checkout($promoteProfile->getProfileId(), $promoteProfile->getKnownAggregateRevision());
        return [$profile->promote($promoteProfile), $metadata];
    }
}
