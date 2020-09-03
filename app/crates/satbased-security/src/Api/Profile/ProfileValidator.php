<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile;

use Daikon\Interop\Assertion;
use Daikon\Validize\Validator\Validator;
use Satbased\Security\ReadModel\Standard\Profile;
use Satbased\Security\ReadModel\Standard\ProfileCollection;
use Satbased\Security\ValueObject\ProfileId;

final class ProfileValidator extends Validator
{
    private ProfileCollection $profileCollection;

    public function __construct(ProfileCollection $profileCollection)
    {
        $this->profileCollection = $profileCollection;
    }

    /** @param mixed $input */
    protected function validate($input): Profile
    {
        Assertion::regex($input, ProfileId::PATTERN, 'Invalid format.');

        $profile = $this->profileCollection->byId($input)->getFirst();
        Assertion::isInstanceOf($profile, Profile::class, 'Not found.');

        return $profile;
    }
}
