<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Verify;

use Daikon\ValueObject\Timestamp;
use Satbased\Security\Profile\ProfileMessageTrait;

/**
 * @map(verifiedAt, Daikon\ValueObject\Timestamp)
 */
trait VerifyMessageTrait
{
    use ProfileMessageTrait;

    private Timestamp $verifiedAt;

    public function getVerifiedAt(): Timestamp
    {
        return $this->verifiedAt;
    }
}
