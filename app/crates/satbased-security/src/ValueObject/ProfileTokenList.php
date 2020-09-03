<?php declare(strict_types=1);

namespace Satbased\Security\ValueObject;

use Daikon\Entity\EntityInterface;
use Daikon\Entity\EntityList;
use Daikon\Interop\Assertion;
use Daikon\ValueObject\Uuid;
use Satbased\Security\Entity\AuthenticationToken;
use Satbased\Security\Entity\VerificationToken;

/**
 * @type(Satbased\Security\Entity\AuthenticationToken)
 * @type(Satbased\Security\Entity\VerificationToken)
 */
final class ProfileTokenList extends EntityList
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

    public function hasAuthenticationToken(): bool
    {
        return !is_null($this->getByType(AuthenticationToken::class));
    }

    public function hasVerificationToken(): bool
    {
        return !is_null($this->getByType(VerificationToken::class));
    }

    public function getAuthenticationToken(): AuthenticationToken
    {
        $token = $this->getByType(AuthenticationToken::class);
        Assertion::isInstanceOf($token, AuthenticationToken::class);
        return $token;
    }

    public function getVerificationToken(): VerificationToken
    {
        $token = $this->getByType(VerificationToken::class);
        Assertion::isInstanceOf($token, VerificationToken::class);
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
