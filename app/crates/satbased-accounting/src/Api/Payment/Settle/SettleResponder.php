<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Settle;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;

final class SettleResponder extends Responder
{
    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new EmptyResponse;
    }
}
