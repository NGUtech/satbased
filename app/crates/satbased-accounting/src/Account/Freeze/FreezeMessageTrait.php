<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Freeze;

use Daikon\ValueObject\Timestamp;
use Satbased\Accounting\Account\AccountMessageTrait;

/**
 * @map(frozenAt, Daikon\ValueObject\Timestamp)
 */
trait FreezeMessageTrait
{
    use AccountMessageTrait;

    private Timestamp $frozenAt;

    public function getFrozenAt(): Timestamp
    {
        return $this->frozenAt;
    }
}
