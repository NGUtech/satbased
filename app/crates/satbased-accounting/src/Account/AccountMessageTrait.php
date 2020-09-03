<?php declare(strict_types=1);

namespace Satbased\Accounting\Account;

use Daikon\EventSourcing\Aggregate\AggregateRevision;
use Daikon\Interop\FromToNativeTrait;
use Satbased\Accounting\ValueObject\AccountId;

/**
 * @id(accountId, Satbased\Accounting\ValueObject\AccountId)
 * @rev(revision, Daikon\EventSourcing\Aggregate\AggregateRevision)
 */
trait AccountMessageTrait
{
    use FromToNativeTrait;

    private AccountId $accountId;

    private AggregateRevision $revision;

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }
}
