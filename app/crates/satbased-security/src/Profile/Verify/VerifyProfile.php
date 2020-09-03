<?php declare(strict_types=1);

namespace Satbased\Security\Profile\Verify;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\ValueObject\Sha256;

/**
 * @map(token, Daikon\ValueObject\Sha256)
 */
final class VerifyProfile implements CommandInterface
{
    use AnnotatesCommand;
    use VerifyMessageTrait;

    private Sha256 $token;

    public function getToken(): Sha256
    {
        return $this->token;
    }
}
