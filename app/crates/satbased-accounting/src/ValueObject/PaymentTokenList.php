<?php declare(strict_types=1);

namespace Satbased\Accounting\ValueObject;

use Daikon\Entity\EntityInterface;
use Daikon\Entity\EntityList;
use Daikon\Interop\Assertion;
use Daikon\ValueObject\Uuid;
use Satbased\Accounting\Entity\ApprovalToken;

/**
 * @type(Satbased\Accounting\Entity\ApprovalToken)
 */
final class PaymentTokenList extends EntityList
{
    /** @return int|bool */
    public function indexOfId(Uuid $tokenId)
    {
        /** @var EntityInterface $token */
        foreach ($this as $index => $token) {
            if ($tokenId->equals($token->getIdentity())) {
                return $index;
            }
        }

        return false;
    }

    public function hasId(Uuid $tokenId): bool
    {
        return $this->indexOfId($tokenId) !== false;
    }

    public function hasApprovalToken(): bool
    {
        return $this->getByType(ApprovalToken::class) instanceof ApprovalToken;
    }

    public function getApprovalToken(): ApprovalToken
    {
        $token = $this->getByType(ApprovalToken::class);
        Assertion::isInstanceOf($token, ApprovalToken::class);
        return $token;
    }

    public function addToken(EntityInterface $token): self
    {
        Assertion::null($this->getByType(get_class($token)));
        Assertion::false($this->hasId($token->getIdentity()));
        return $this->push($token);
    }

    public function removeToken(EntityInterface $token): self
    {
        Assertion::true($this->hasId($token->getIdentity()));
        return $this->without($this->indexOfId($token->getIdentity()));
    }

    public function replaceToken(EntityInterface $token, EntityInterface $update): self
    {
        $index = $this->indexOfId($token->getIdentity());
        return $this->with($index, $update);
    }

    public function getByType(string $type): ?EntityInterface
    {
        $this->assertValidType($type);
        foreach ($this->compositeVector as $token) {
            if ($token instanceof $type) {
                return $token;
            }
        }
        return null;
    }
}
