<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile;

use Daikon\Interop\Assertion;
use Daikon\Validize\Validator\Validator;
use Daikon\ValueObject\Email;
use Satbased\Security\ReadModel\Standard\ProfileCollection;

final class EmailNotRegisteredValidator extends Validator
{
    private ProfileCollection $profileCollection;
    
    public function __construct(ProfileCollection $profileCollection)
    {
        $this->profileCollection = $profileCollection;
    }

    /** @param mixed $input */
    protected function validate($input): void
    {
        $email = Email::fromNative($input);
        $profile = $this->profileCollection->byEmail((string)$email)->getFirst();
        Assertion::null($profile, 'Email already registered.');
    }
}
