<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Request;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Accounting\Payment\Request\RequestPayment;

final class RequestResponder extends Responder
{
    private RequestPayment $requestPayment;

    public function __construct(RequestPayment $requestPayment)
    {
        $this->requestPayment = $requestPayment;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new JsonResponse($this->requestPayment->toNative(), self::STATUS_CREATED);
    }
}
