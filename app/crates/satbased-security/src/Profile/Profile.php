<?php declare(strict_types=1);

namespace Satbased\Security\Profile;

use Daikon\EventSourcing\Aggregate\AggregateRoot;
use Daikon\Interop\Assertion;
use Daikon\ValueObject\Email;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\Timestamp;
use Satbased\Security\Profile\Close\ProfileClosed;
use Satbased\Security\Profile\Close\CloseProfile;
use Satbased\Security\Profile\Login\ProfileLoggedIn;
use Satbased\Security\Profile\Login\LoginProfile;
use Satbased\Security\Profile\Logout\ProfileLoggedOut;
use Satbased\Security\Profile\Logout\LogoutProfile;
use Satbased\Security\Profile\Register\RegisterProfile;
use Satbased\Security\Profile\Register\ProfileRegistered;
use Satbased\Security\Profile\Token\AuthenticationTokenAdded;
use Satbased\Security\Profile\Token\AuthenticationTokenExpired;
use Satbased\Security\Profile\Token\AuthenticationTokenRefreshed;
use Satbased\Security\Profile\Token\VerificationTokenAdded;
use Satbased\Security\Profile\Token\VerificationTokenRemoved;
use Satbased\Security\Profile\Verify\ProfileVerified;
use Satbased\Security\Profile\Verify\VerifyProfile;
use Satbased\Security\Entity\AuthenticationToken;
use Satbased\Security\Entity\VerificationToken;
use Satbased\Security\Profile\Promote\ProfilePromoted;
use Satbased\Security\Profile\Promote\PromoteProfile;
use Satbased\Security\ValueObject\ProfileRole;
use Satbased\Security\ValueObject\ProfileState;
use Satbased\Security\ValueObject\ProfileTokenList;
use Satbased\Security\ValueObject\PasswordHash;

final class Profile extends AggregateRoot
{
    use ProfileTrait;

    private Text $name;

    private Email $email;

    private PasswordHash $passwordHash;

    private Text $language;

    private ProfileRole $role;

    private Timestamp $registeredAt;

    private Timestamp $verifiedAt;

    private Timestamp $closedAt;

    private ProfileTokenList $tokens;

    private ProfileState $state;

    public static function register(RegisterProfile $registerProfile): self
    {
        return (new self($registerProfile->getProfileId()))
            ->reflectThat(ProfileRegistered::fromCommand($registerProfile))
            ->reflectThat(AuthenticationTokenAdded::fromCommand($registerProfile))
            ->reflectThat(VerificationTokenAdded::fromCommand($registerProfile));
    }

    public function login(LoginProfile $loginProfile): self
    {
        Assertion::true($this->canBeLoggedIn(), 'Profile cannot be logged in.');

        return $this
            ->reflectThat(ProfileLoggedIn::fromCommand($loginProfile))
            ->reflectThat(AuthenticationTokenRefreshed::fromCommand($loginProfile));
    }

    public function logout(LogoutProfile $logoutProfile): self
    {
        return $this
            ->reflectThat(ProfileLoggedOut::fromCommand($logoutProfile))
            ->reflectThat(AuthenticationTokenExpired::fromLogout($logoutProfile));
    }

    public function verify(VerifyProfile $verifyProfile): self
    {
        Assertion::true($this->canBeVerified(), 'Profile cannot be verified.');

        Assertion::true(
            $this->getTokens()->getVerificationToken()->verify(
                $verifyProfile->getToken(),
                $verifyProfile->getVerifiedAt()
            ),
            'Token is not verified.'
        );

        return $this
            ->reflectThat(ProfileVerified::fromCommand($verifyProfile))
            ->reflectThat(VerificationTokenRemoved::fromVerify($verifyProfile));
    }

    public function close(CloseProfile $closeProfile): self
    {
        Assertion::true($this->canBeClosed(), 'Profile cannot be closed.');

        $profile = $this
            ->reflectThat(ProfileClosed::fromCommand($closeProfile))
            ->reflectThat(AuthenticationTokenExpired::fromClose($closeProfile));

        if ($this->tokens->hasVerificationToken()) {
            $profile->reflectThat(VerificationTokenRemoved::fromClose($closeProfile));
        }

        return $profile;
    }

    public function promote(PromoteProfile $promoteProfile): self
    {
        Assertion::true($this->canBePromoted(), 'Profile cannot be promoted.');

        return $this->reflectThat(ProfilePromoted::fromCommand($promoteProfile));
    }

    protected function whenProfileRegistered(ProfileRegistered $profileRegistered): void
    {
        $this->name = $profileRegistered->getName();
        $this->email = $profileRegistered->getEmail();
        $this->passwordHash = $profileRegistered->getPasswordHash();
        $this->language = $profileRegistered->getLanguage();
        $this->role = $profileRegistered->getRole();
        $this->registeredAt = $profileRegistered->getRegisteredAt();
        $this->verifiedAt = Timestamp::makeEmpty();
        $this->closedAt = Timestamp::makeEmpty();
        $this->tokens = ProfileTokenList::makeEmpty();
        //@todo hardcode registered state
        $this->state = $profileRegistered->getState();
    }

    protected function whenProfileLoggedIn(ProfileLoggedIn $profileLoggedIn): void
    {
    }

    protected function whenProfileLoggedOut(ProfileLoggedOut $profileLoggedOut): void
    {
    }

    protected function whenProfileVerified(ProfileVerified $profileVerified): void
    {
        $this->verifiedAt = $profileVerified->getVerifiedAt();
        $this->state = ProfileState::fromNative(ProfileState::VERIFIED);
    }

    protected function whenProfileClosed(ProfileClosed $profileClosed): void
    {
        $this->closedAt = $profileClosed->getClosedAt();
        $this->state = ProfileState::fromNative(ProfileState::CLOSED);
    }

    protected function whenProfilePromoted(ProfilePromoted $profilePromoted): void
    {
        $this->role = $profilePromoted->getRole();
    }

    protected function whenAuthenticationTokenAdded(AuthenticationTokenAdded $tokenAdded): void
    {
        $this->tokens = $this->tokens->addToken(AuthenticationToken::fromNative($tokenAdded->toNative()));
    }

    protected function whenAuthenticationTokenExpired(AuthenticationTokenExpired $tokenExpired): void
    {
        $token = $this->tokens->getAuthenticationToken();
        $update = AuthenticationToken::fromNative($tokenExpired->toNative());
        $this->tokens = $this->tokens->replaceToken($token, $update);
    }

    protected function whenAuthenticationTokenRefreshed(AuthenticationTokenRefreshed $tokenRefreshed): void
    {
        $token = $this->tokens->getAuthenticationToken();
        $update = AuthenticationToken::fromNative(array_merge($token->toNative(), $tokenRefreshed->toNative()));
        $this->tokens = $this->tokens->replaceToken($token, $update);
    }

    protected function whenVerificationTokenAdded(VerificationTokenAdded $tokenAdded): void
    {
        $this->tokens = $this->tokens->addToken(VerificationToken::fromNative($tokenAdded->toNative()));
    }

    protected function whenVerificationTokenRemoved(VerificationTokenRemoved $tokenRemoved): void
    {
        $token = $this->tokens->getVerificationToken();
        $this->tokens = $this->tokens->removeToken($token);
    }
}
