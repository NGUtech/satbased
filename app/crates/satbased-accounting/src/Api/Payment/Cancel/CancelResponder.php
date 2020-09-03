<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Cancel;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Accounting\Payment\Cancel\CancelPayment;

final class CancelResponder extends Responder
{
    private ?CancelPayment $cancelPayment;

    public function __construct(?CancelPayment $cancelPayment)
    {
        $this->cancelPayment = $cancelPayment;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        if ($this->cancelPayment instanceof CancelPayment) {
            return new JsonResponse($this->cancelPayment->toNative(), self::STATUS_OK);
        } else {
            return new EmptyResponse;
        }
    }
}
