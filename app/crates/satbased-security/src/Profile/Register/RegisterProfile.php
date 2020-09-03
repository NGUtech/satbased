<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Register;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\ValueObject\Timestamp;

/**
 * @map(verificationTokenExpiresAt, Daikon\ValueObject\Timestamp)
 */
final class RegisterProfile implements CommandInterface
{
    use AnnotatesCommand;
    use RegisterMessageTrait;

    /** @var Timestamp */
    private $verificationTokenExpiresAt;

    public function getVerificationTokenExpiresAt(): Timestamp
    {
        return $this->verificationTokenExpiresAt;
    }
}
