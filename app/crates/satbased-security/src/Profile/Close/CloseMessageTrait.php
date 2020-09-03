<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Close;

use Daikon\ValueObject\Timestamp;
use Satbased\Security\Profile\ProfileMessageTrait;

/**
 * @map(closedAt, Daikon\ValueObject\Timestamp)
 */
trait CloseMessageTrait
{
    use ProfileMessageTrait;

    private Timestamp $closedAt;

    public function getClosedAt(): Timestamp
    {
        return $this->closedAt;
    }
}
