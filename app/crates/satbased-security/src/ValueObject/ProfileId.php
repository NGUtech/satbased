<?php declare(strict_types=1);

namespace Satbased\Security\ValueObject;

use Daikon\EventSourcing\Aggregate\AggregateId;

final class ProfileId extends AggregateId
{
    public const PREFIX = 'satbased.security.profile';

    public const PATTERN = '/^satbased\.security\.profile-[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}$/';
}
