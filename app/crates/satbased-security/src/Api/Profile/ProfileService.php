<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile;

use Daikon\Boot\Service\Provisioner\MessageBusProvisioner;
use Daikon\Config\ConfigProviderInterface;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\Interop\Assertion;
use Daikon\MessageBus\MessageBusInterface;
use Daikon\Metadata\MetadataInterface;
use Daikon\ValueObject\Timestamp;
use Daikon\ValueObject\Uuid;
use Firebase\JWT\JWT;
use Daikon\Security\Authentication\AuthenticatorInterface;
use Daikon\Security\Authentication\JwtAuthenticationServiceInterface;
use Daikon\Security\Exception\AuthenticationException;
use Daikon\Security\Exception\AuthorizationException;
use Daikon\ValueObject\Sha256;
use Satbased\Security\Profile\Close\CloseProfile;
use Satbased\Security\Profile\Login\LoginProfile;
use Satbased\Security\Profile\Logout\LogoutProfile;
use Satbased\Security\Profile\Promote\PromoteProfile;
use Satbased\Security\Profile\Register\RegisterProfile;
use Satbased\Security\Profile\Verify\VerifyProfile;
use Satbased\Security\ReadModel\Standard\Profile;
use Satbased\Security\ReadModel\Standard\ProfileCollection;
use Satbased\Security\ValueObject\ProfileId;
use Satbased\Security\ValueObject\ProfileRole;
use Satbased\Security\ValueObject\ProfileState;
use Satbased\Security\ValueObject\PasswordHash;

final class ProfileService implements JwtAuthenticationServiceInterface
{
    const DEFAULT_TOKEN_TTL = '+1 month';

    private ConfigProviderInterface $config;

    private MessageBusInterface $messageBus;

    private ProfileCollection $profileCollection;

    public function __construct(
        ConfigProviderInterface $config,
        MessageBusInterface $messageBus,
        ProfileCollection $profileCollection
    ) {
        $this->config = $config;
        $this->messageBus = $messageBus;
        $this->profileCollection = $profileCollection;
    }

    public function register(array $payload): RegisterProfile
    {
        $this->assertInput($payload, ['name', 'email', 'password', 'language']);

        $profile = $this->profileCollection->byEmail((string)$payload['email'])->getFirst();
        Assertion::null($profile, 'Email already registered.');

        $registerProfile = RegisterProfile::fromNative([
            'profileId' => ProfileId::PREFIX.'-'.Uuid::generate(),
            'name' => (string)$payload['name'],
            'email' => (string)$payload['email'],
            'passwordHash' => (string)PasswordHash::gen($payload['password']),
            'language' => (string)$payload['language'],
            'role' => ProfileRole::CUSTOMER,
            'state' => ProfileState::PENDING,
            'registeredAt' => (string)Timestamp::now(),
            'verificationTokenExpiresAt' => (string)$this->getVerificationTokenExpiryTime()
        ]);

        $this->dispatch($registerProfile);

        return $registerProfile;
    }

    public function verify(Profile $profile, Sha256 $token): VerifyProfile
    {
        $now = Timestamp::now();

        Assertion::true($profile->canBeVerified(), 'Profile cannot be verified.');
        Assertion::true($profile->getTokens()->getVerificationToken()->verify($token, $now), 'Token is not verified.');

        $verifyProfile = VerifyProfile::fromNative([
            'profileId' => (string)$profile->getProfileId(),
            'revision' => (string)$profile->getRevision(),
            'verifiedAt' => (string)$now,
            'token' => (string)$token
        ]);

        $this->dispatch($verifyProfile);

        return $verifyProfile;
    }

    public function authenticate(string $email, string $password): AuthenticatorInterface
    {
        /** @var Profile $profile */
        $profile = $this->profileCollection->byEmail($email)->getFirst();

        if (!$profile instanceof Profile) {
            throw new AuthenticationException('Profile not found.');
        }

        if (!$profile->getPasswordHash()->verify($password)) {
            throw new AuthenticationException('Incorrect password.');
        }

        return $profile;
    }

    public function authenticateJWT(string $id, string $jti, string $xsrf): AuthenticatorInterface
    {
        /** @var Profile $profile */
        $profile = $this->profileCollection->byId($id)->getFirst();

        if (!$profile instanceof Profile) {
            throw new AuthenticationException('Profile not found.');
        }

        if (!$profile->getTokens()->getAuthenticationToken()->authenticate(
            Uuid::fromNative($jti),
            Sha256::fromNative($xsrf),
            Timestamp::now()
        )) {
            throw new AuthenticationException('Token authentication failed.');
        }

        return $profile;
    }

    public function login(Profile $profile): LoginProfile
    {
        if (!$profile->canBeLoggedIn()) {
            throw new AuthorizationException('Profile cannot be logged in.');
        }

        $loginProfile = LoginProfile::fromNative([
            'profileId' => (string)$profile->getProfileId(),
            'revision' => (string)$profile->getRevision(),
            'authenticationTokenExpiresAt' => (string)$this->getAuthenticationTokenExpiryTime()
        ]);

        $this->dispatch($loginProfile);

        return $loginProfile;
    }

    public function logout(string $profileId): LogoutProfile
    {
        //@todo canBeLoggedOut check for non expired authcookie
        $logoutProfile = LogoutProfile::fromNative([
            'profileId' => $profileId
        ]);

        $this->dispatch($logoutProfile);

        return $logoutProfile;
    }

    public function close(Profile $profile): CloseProfile
    {
        Assertion::true($profile->canBeClosed(), 'Profile cannot be closed.');

        $closeProfile = CloseProfile::fromNative([
            'profileId' => (string)$profile->getProfileId(),
            'revision' => (string)$profile->getRevision(),
            'closedAt' => (string)Timestamp::now()
        ]);

        $this->dispatch($closeProfile);

        return $closeProfile;
    }

    public function promote(Profile $profile, ProfileRole $role): PromoteProfile
    {
        Assertion::true($profile->canBePromoted(), 'Profile cannot be promoted.');

        $promoteProfile = PromoteProfile::fromNative([
            'profileId' => (string)$profile->getProfileId(),
            'revision' => (string)$profile->getRevision(),
            'role' => (string)$role
        ]);

        $this->dispatch($promoteProfile);

        return $promoteProfile;
    }

    public function generateJWT(Profile $profile): string
    {
        $jwtConfig = $this->config->get('project.authentication.cookies.jwt');
        if (empty($jwtConfig)) {
            throw new AuthenticationException('JWT configuration is missing.');
        }

        $timestamp = Timestamp::now()->toTime();
        $authenticationToken = $profile->getTokens()->getAuthenticationToken();

        return JWT::encode([
            'iss' => $jwtConfig['issuer'],
            'aud' => $jwtConfig['audience'],
            'exp' => $this->getAuthenticationTokenExpiryTime()->toTime(),
            'nbf' => $timestamp,
            'iat' => $timestamp,
            'jti' => (string)$authenticationToken->getIdentity(),
            'xsrf' => (string)$authenticationToken->getToken(),
            'uid' => (string)$profile->getProfileId()
        ], $jwtConfig['secret']);
    }

    private function dispatch(CommandInterface $command, MetadataInterface $metadata = null): void
    {
        $this->messageBus->publish($command, MessageBusProvisioner::COMMANDS_CHANNEL, $metadata);
    }

    private function getAuthenticationTokenExpiryTime(): Timestamp
    {
        return Timestamp::now()->modify(
            $this->config->get(
                'crates.satbased.security.authentication.ttl',
                self::DEFAULT_TOKEN_TTL
            )
        );
    }

    private function getVerificationTokenExpiryTime(): Timestamp
    {
        return Timestamp::now()->modify(
            $this->config->get(
                'crates.satbased.security.verification.ttl',
                self::DEFAULT_TOKEN_TTL
            )
        );
    }

    private function assertInput(array $payload, array $expectedInput): void
    {
        $missingInput = array_diff($expectedInput, array_keys($payload));
        Assertion::true(empty($missingInput), "Missing required input '".implode(', ', $missingInput)."'.");
    }
}
