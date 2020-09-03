<?php declare(strict_types=1);

namespace Satbased\Accounting\ValueObject;

use Daikon\EventSourcing\Aggregate\AggregateId;

final class AccountId extends AggregateId
{
    public const PREFIX = 'satbased.accounting.account';

    public const PATTERN = '/^satbased\.accounting\.account-[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/';
}
