<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Approve;

use Daikon\ValueObject\Timestamp;
use Satbased\Accounting\Payment\PaymentMessageTrait;

/**
 * @map(approvedAt, Daikon\ValueObject\Timestamp)
 */
trait ApproveMessageTrait
{
    use PaymentMessageTrait;

    private Timestamp $approvedAt;

    public function getApprovedAt(): Timestamp
    {
        return $this->approvedAt;
    }
}
