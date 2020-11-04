<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Update;

use Satbased\Accounting\Payment\PaymentMessageTrait;
use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;

/**
 * @map(references, Daikon\ValueObject\TextMap)
 * @map(updatedAt, Daikon\ValueObject\Timestamp)
 */
trait UpdateMessageTrait
{
    use PaymentMessageTrait;

    private TextMap $references;

    private Timestamp $updatedAt;

    public function getReferences(): TextMap
    {
        return $this->references;
    }

    public function getUpdatedAt(): Timestamp
    {
        return $this->updatedAt;
    }
}
