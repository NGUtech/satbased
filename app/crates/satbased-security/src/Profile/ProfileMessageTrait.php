<?php declare(strict_types=1);

namespace Satbased\Security\Profile;

use Daikon\EventSourcing\Aggregate\AggregateRevision;
use Daikon\Interop\FromToNativeTrait;
use Satbased\Security\ValueObject\ProfileId;

/**
 * @id(profileId, Satbased\Security\ValueObject\ProfileId)
 * @rev(revision, Daikon\EventSourcing\Aggregate\AggregateRevision)
 */
trait ProfileMessageTrait
{
    use FromToNativeTrait;

    private ProfileId $profileId;

    private AggregateRevision $revision;

    public function getProfileId(): ProfileId
    {
        return $this->profileId;
    }
}
