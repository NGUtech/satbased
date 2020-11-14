<?php declare(strict_types=1);

namespace Satbased\Accounting\Entity;

use Daikon\Entity\Attribute;
use Daikon\Entity\AttributeMap;
use Daikon\Entity\Entity;
use Daikon\ValueObject\Sha256;
use Daikon\ValueObject\Timestamp;
use Daikon\ValueObject\Uuid;

final class ApprovalToken extends Entity
{
    public static function getAttributeMap(): AttributeMap
    {
        return new AttributeMap([
            Attribute::define('id', Uuid::class),
            Attribute::define('token', Sha256::class),
            Attribute::define('expiresAt', Timestamp::class)
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

    public function getToken(): Sha256
    {
        return $this->get('token');
    }

    public function getExpiresAt(): Timestamp
    {
        return $this->get('expiresAt');
    }

    public function approve(Sha256 $token, Timestamp $time): bool
    {
        return $this->getToken()->equals($token) && $this->getExpiresAt()->isAfter($time);
    }
}
