<?php declare(strict_types=1);

namespace Satbased\Security\Tests\Unit\Profile;

use PHPUnit\Framework\TestCase;
use Satbased\Security\Profile\Register\RegisterProfile;
use Satbased\Security\Profile\Profile;

class ProfileTest extends TestCase
{
    public function testStartAggregateRootLifecycle(): void
    {
        $profileId = 'satbased.security.profile-f83ac208-59a7-44e2-bae7-8579a1f00b1f';
        $registerProfile = RegisterProfile::fromNative([
            'profileId' => $profileId,
            'passwordHash' => 'xyz',
            'email' => 'you@example.dev',
            'role' => 'staff',
            'state' => 'verified',
            'authenticationTokenExpiresAt' => '2030-01-01T00:00:00.000000+00:00',
            'verificationTokenExpiresAt' => '2029-01-01T00:00:00.000000+00:00',
        ]);
        $profile = Profile::register($registerProfile);

        $this->assertEquals($profileId, $profile->getIdentifier());
        $this->assertEquals(3, $profile->getRevision()->toNative());
        $this->assertCount(3, $profile->getTrackedEvents());
    }
}
