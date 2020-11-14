<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Register;

use Daikon\ValueObject\Email;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\Timestamp;
use Satbased\Security\Profile\ProfileMessageTrait;
use Satbased\Security\ValueObject\ProfileRole;
use Satbased\Security\ValueObject\ProfileState;
use Satbased\Security\ValueObject\PasswordHash;

/**
 * @map(name, Daikon\ValueObject\Text)
 * @map(email, Daikon\ValueObject\Email)
 * @map(passwordHash, Satbased\Security\ValueObject\PasswordHash)
 * @map(language, Daikon\ValueObject\Text)
 * @map(role, Satbased\Security\ValueObject\ProfileRole)
 * @map(state, Satbased\Security\ValueObject\ProfileState)
 * @map(registeredAt, Daikon\ValueObject\Timestamp)
 * @map(verificationTokenExpiresAt, Daikon\ValueObject\Timestamp)
 */
trait RegisterMessageTrait
{
    use ProfileMessageTrait;

    private Text $name;

    private Email $email;

    private PasswordHash $passwordHash;

    private Text $language;

    private ProfileRole $role;

    private ProfileState $state;

    private Timestamp $registeredAt;

    private Timestamp $verificationTokenExpiresAt;

    public function getName(): Text
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function getLanguage(): Text
    {
        return $this->language;
    }

    public function getRole(): ProfileRole
    {
        return $this->role;
    }

    public function getState(): ProfileState
    {
        return $this->state;
    }

    public function getRegisteredAt(): Timestamp
    {
        return $this->registeredAt;
    }

    public function getVerificationTokenExpiresAt(): Timestamp
    {
        return $this->verificationTokenExpiresAt;
    }
}
