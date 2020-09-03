<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Promote;

use Satbased\Security\Profile\ProfileMessageTrait;
use Satbased\Security\ValueObject\ProfileRole;

/**
 * @map(role, Satbased\Security\ValueObject\ProfileRole)
 */
trait PromoteMessageTrait
{
    use ProfileMessageTrait;

    private ProfileRole $role;

    public function getRole(): ProfileRole
    {
        return $this->role;
    }
}
