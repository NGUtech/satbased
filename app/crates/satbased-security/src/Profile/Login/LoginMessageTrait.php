<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Login;

use Daikon\ValueObject\Timestamp;
use Satbased\Security\Profile\ProfileMessageTrait;

/**
 * @map(authenticationTokenExpiresAt, Daikon\ValueObject\Timestamp)
 */
trait LoginMessageTrait
{
    use ProfileMessageTrait;

    private Timestamp $authenticationTokenExpiresAt;

    public function getAuthenticationTokenExpiresAt(): Timestamp
    {
        return $this->authenticationTokenExpiresAt;
    }
}
