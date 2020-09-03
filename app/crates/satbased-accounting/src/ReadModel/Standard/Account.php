<?php declare(strict_types=1);

namespace Satbased\Accounting\ReadModel\Standard;

use Daikon\Entity\Attribute;
use Daikon\Entity\AttributeMap;
use Daikon\Entity\Entity;
use Daikon\EventSourcing\Aggregate\AggregateRevision;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Daikon\ReadModel\Projection\EventHandlerTrait;
use Daikon\ReadModel\Projection\ProjectionInterface;
use Daikon\ValueObject\Timestamp;
use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use NGUtech\Bitcoin\ValueObject\BitcoinWallet;
use Satbased\Accounting\Account\AccountTrait;
use Satbased\Accounting\Account\Credit\AccountCredited;
use Satbased\Accounting\Account\Debit\AccountDebited;
use Satbased\Accounting\Account\Freeze\AccountFrozen;
use Satbased\Accounting\Account\Open\AccountOpened;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\AccountState;
use Satbased\Security\ValueObject\ProfileId;

final class Account extends Entity implements ProjectionInterface, ProprietaryInterface, ResourceInterface
{
    use AccountTrait;
    use EventHandlerTrait;

    public static function getAttributeMap(): AttributeMap
    {
        return new AttributeMap([
            Attribute::define('accountId', AccountId::class),
            Attribute::define('revision', AggregateRevision::class),
            Attribute::define('profileId', ProfileId::class),
            Attribute::define('wallet', BitcoinWallet::class),
            Attribute::define('state', AccountState::class),
            Attribute::define('openedAt', Timestamp::class),
            Attribute::define('frozenAt', Timestamp::class),
        ]);
    }

    public function getResourceId(): string
    {
        return self::class;
    }

    public function getOwnerId(): string
    {
        return (string)$this->getProfileId();
    }

    public function getIdentity(): AccountId
    {
        return $this->getAccountId();
    }

    public function adaptRevision(DomainEventInterface $event): self
    {
        return $this->withValue('revision', $event->getAggregateRevision());
    }

    public function whenAccountOpened(AccountOpened $accountOpened): self
    {
        return $this
            ->withValues($accountOpened->toNative())
            ->withValue('state', AccountState::OPENED);
    }

    public function whenAccountCredited(AccountCredited $accountCredited): self
    {
        return $this
            ->adaptRevision($accountCredited)
            ->withValue('wallet', $this->getWallet()->credit($accountCredited->getAmount()));
    }

    public function whenAccountDebited(AccountDebited $accountDebited): self
    {
        return $this
            ->adaptRevision($accountDebited)
            ->withValue('wallet', $this->getWallet()->debit($accountDebited->getAmount()));
    }

    public function whenAccountFrozen(AccountFrozen $accountFrozen): self
    {
        return $this
            ->adaptRevision($accountFrozen)
            ->withValue('frozenAt', $accountFrozen->getFrozenAt())
            ->withValue('state', AccountState::FROZEN);
    }
}
