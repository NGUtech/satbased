<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Open;

use Daikon\ValueObject\Timestamp;
use Satbased\Accounting\Account\AccountMessageTrait;
use Satbased\Security\ValueObject\ProfileId;

/**
 * @map(profileId, Satbased\Security\ValueObject\ProfileId)
 * @map(openedAt, Daikon\ValueObject\Timestamp)
 */
trait OpenMessageTrait
{
    use AccountMessageTrait;

    private ProfileId $profileId;

    private Timestamp $openedAt;

    public function getProfileId(): ProfileId
    {
        return $this->profileId;
    }

    public function getOpenedAt(): Timestamp
    {
        return $this->openedAt;
    }
}
