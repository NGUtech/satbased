<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile;

use Daikon\Interop\Assertion;
use Daikon\Validize\Validator\Validator;
use Satbased\Security\ValueObject\ProfileState;

final class ProfileStateValidator extends Validator
{
    /** @param mixed $input */
    protected function validate($input): string
    {
        Assertion::string($input, 'Must be a string.');
        $state = trim($input);
        Assertion::inArray($state, ProfileState::STATES, 'Not a valid profile state.');
        return $state;
    }
}
