<?php declare(strict_types=1);

namespace Satbased\Accounting\Payment\Approve;

use Daikon\EventSourcing\Aggregate\Command\AnnotatesCommand;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\ValueObject\Sha256;

/**
 * @map(token, Daikon\ValueObject\Sha256)
 */
final class ApprovePayment implements CommandInterface
{
    use AnnotatesCommand;
    use ApproveMessageTrait;

    private Sha256 $token;

    public function getToken(): Sha256
    {
        return $this->token;
    }
}
