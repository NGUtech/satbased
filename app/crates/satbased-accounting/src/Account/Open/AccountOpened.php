<?php declare(strict_types=1);

namespace Satbased\Accounting\Account\Open;

use Daikon\EventSourcing\Aggregate\Event\AnnotatesDomainEvent;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;

final class AccountOpened implements DomainEventInterface
{
    use AnnotatesDomainEvent;
    use OpenMessageTrait;

    public static function fromCommand(OpenAccount $openAccount): self
    {
        return self::fromNative($openAccount->toNative());
    }

    public function conflictsWith(DomainEventInterface $otherEvent): bool
    {
        return $otherEvent instanceof $this;
    }
}
