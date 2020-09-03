<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile;

use Daikon\Interop\Assert;
use Daikon\Validize\Validator\Validator;
use Satbased\Security\ValueObject\ProfileRole;

final class RoleValidator extends Validator
{
    /** @param mixed $input */
    protected function validate($input): ProfileRole
    {
        Assert::that($input, 'Invalid format.')->string()->notBlank();

        return ProfileRole::fromNative($input);
    }
}
