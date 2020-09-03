<?php declare(strict_types=1);

namespace Satbased\Security\ReadModel\Standard;

use Daikon\Entity\Attribute;
use Daikon\Entity\AttributeMap;
use Daikon\Entity\Entity;
use Daikon\EventSourcing\Aggregate\AggregateRevision;
use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Daikon\ReadModel\Projection\EventHandlerTrait;
use Daikon\ReadModel\Projection\ProjectionInterface;
use Daikon\Security\Authentication\AuthenticatorInterface;
use Daikon\ValueObject\Email;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\Timestamp;
use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Satbased\Security\Profile\ProfileTrait;
use Satbased\Security\Profile\Close\ProfileClosed;
use Satbased\Security\Profile\Login\ProfileLoggedIn;
use Satbased\Security\Profile\Logout\ProfileLoggedOut;
use Satbased\Security\Profile\Register\ProfileRegistered;
use Satbased\Security\Profile\Token\AuthenticationTokenAdded;
use Satbased\Security\Profile\Token\AuthenticationTokenExpired;
use Satbased\Security\Profile\Token\AuthenticationTokenRefreshed;
use Satbased\Security\Profile\Token\VerificationTokenAdded;
use Satbased\Security\Profile\Token\VerificationTokenRemoved;
use Satbased\Security\Profile\Verify\ProfileVerified;
use Satbased\Security\Entity\AuthenticationToken;
use Satbased\Security\Entity\VerificationToken;
use Satbased\Security\Profile\Promote\ProfilePromoted;
use Satbased\Security\ValueObject\ProfileId;
use Satbased\Security\ValueObject\ProfileRole;
use Satbased\Security\ValueObject\ProfileState;
use Satbased\Security\ValueObject\ProfileTokenList;
use Satbased\Security\ValueObject\PasswordHash;

final class Profile extends Entity implements
    AuthenticatorInterface,
    ProjectionInterface,
    ProprietaryInterface,
    ResourceInterface,
    RoleInterface
{
    use ProfileTrait;
    use EventHandlerTrait;

    public static function getAttributeMap(): AttributeMap
    {
        return new AttributeMap([
            Attribute::define('profileId', ProfileId::class),
            Attribute::define('revision', AggregateRevision::class),
            Attribute::define('name', Text::class),
            Attribute::define('email', Email::class),
            Attribute::define('passwordHash', PasswordHash::class),
            Attribute::define('language', Text::class),
            Attribute::define('role', ProfileRole::class),
            Attribute::define('state', ProfileState::class),
            Attribute::define('registeredAt', Timestamp::class),
            Attribute::define('verifiedAt', Timestamp::class),
            Attribute::define('closedAt', Timestamp::class),
            Attribute::define('tokens', ProfileTokenList::class),
        ]);
    }

    public function getResourceId(): string
    {
        return self::class;
    }

    public function getRoleId(): string
    {
        return (string)$this->getRole();
    }

    public function getOwnerId(): string
    {
        return (string)$this->getProfileId();
    }

    public function isOwnerOf(ProprietaryInterface $proprietary): bool
    {
        return $this->getOwnerId() === $proprietary->getOwnerId();
    }

    public function getIdentity(): ProfileId
    {
        return $this->getProfileId();
    }

    public function adaptRevision(DomainEventInterface $event): self
    {
        return $this->withValue('revision', $event->getAggregateRevision());
    }

    protected function whenProfileRegistered(ProfileRegistered $profileRegistered): self
    {
        //@todo hard code initial state
        return $this->withValues($profileRegistered->toNative());
    }

    protected function whenProfileLoggedIn(ProfileLoggedIn $profileLoggedIn): self
    {
        return $this->adaptRevision($profileLoggedIn);
    }

    protected function whenProfileLoggedOut(ProfileLoggedOut $profileLoggedOut): self
    {
        return $this->adaptRevision($profileLoggedOut);
    }

    protected function whenProfileVerified(ProfileVerified $profileVerified): self
    {
        return $this
            ->adaptRevision($profileVerified)
            ->withValue('verifiedAt', $profileVerified->getVerifiedAt())
            ->withValue('state', ProfileState::VERIFIED);
    }

    protected function whenProfileClosed(ProfileClosed $profileClosed): self
    {
        return $this
            ->adaptRevision($profileClosed)
            ->withValue('closedAt', $profileClosed->getClosedAt())
            ->withValue('state', ProfileState::CLOSED);
    }

    protected function whenProfilePromoted(ProfilePromoted $profilePromoted): self
    {
        return $this
            ->adaptRevision($profilePromoted)
            ->withValue('role', $profilePromoted->getRole());
    }

    protected function whenAuthenticationTokenAdded(AuthenticationTokenAdded $tokenAdded): self
    {
        $token = AuthenticationToken::fromNative($tokenAdded->toNative());

        return $this
            ->adaptRevision($tokenAdded)
            ->withValue('tokens', $this->getTokens()->addToken($token));
    }

    protected function whenAuthenticationTokenExpired(AuthenticationTokenExpired $tokenExpired): self
    {
        $token = $this->getTokens()->getAuthenticationToken();
        $update = AuthenticationToken::fromNative($tokenExpired->toNative());

        return $this
            ->adaptRevision($tokenExpired)
            ->withValue('tokens', $this->getTokens()->replaceToken($token, $update));
    }

    protected function whenAuthenticationTokenRefreshed(AuthenticationTokenRefreshed $tokenRefreshed): self
    {
        $token = $this->getTokens()->getAuthenticationToken();
        $update = AuthenticationToken::fromNative(array_merge($token->toNative(), $tokenRefreshed->toNative()));

        return $this
            ->adaptRevision($tokenRefreshed)
            ->withValue('tokens', $this->getTokens()->replaceToken($token, $update));
    }

    protected function whenVerificationTokenAdded(VerificationTokenAdded $tokenAdded): self
    {
        $token = VerificationToken::fromNative($tokenAdded->toNative());

        return $this
            ->adaptRevision($tokenAdded)
            ->withValue('tokens', $this->getTokens()->addToken($token));
    }

    protected function whenVerificationTokenRemoved(VerificationTokenRemoved $tokenRemoved): self
    {
        $token = $this->getTokens()->getVerificationToken();

        return $this
            ->adaptRevision($tokenRemoved)
            ->withValue('tokens', $this->getTokens()->removeToken($token));
    }
}
