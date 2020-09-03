<?php declare(strict_types=1);

namespace Satbased\Accounting\Entity;

use Daikon\Entity\Attribute;
use Daikon\Entity\AttributeMap;
use Daikon\Entity\Entity;
use Daikon\Money\Entity\TransactionInterface;
use Daikon\ValueObject\Uuid;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\PaymentId;
use Satbased\Security\ValueObject\ProfileId;

final class BalanceTransfer extends Entity implements TransactionInterface
{
    public static function getAttributeMap(): AttributeMap
    {
        return new AttributeMap([
            Attribute::define('id', Uuid::class),
            Attribute::define('paymentId', PaymentId::class),
            Attribute::define('profileId', ProfileId::class),
            Attribute::define('accountId', AccountId::class),
            Attribute::define('amount', Bitcoin::class),
            Attribute::define('feeEstimate', Bitcoin::class)
        ]);
    }

    public function getIdentity(): Uuid
    {
        return $this->getId();
    }

    public function getId(): Uuid
    {
        return $this->get('id');
    }

    public function getPaymentId(): PaymentId
    {
        return $this->get('paymentId');
    }

    public function getProfileId(): ProfileId
    {
        return $this->get('profileId');
    }

    public function getAccountId(): AccountId
    {
        return $this->get('accountId');
    }

    public function getAmount(): Bitcoin
    {
        return $this->get('amount') ?? Bitcoin::makeEmpty();
    }

    public function getFeeEstimate(): Bitcoin
    {
        return Bitcoin::zero();
    }
}
