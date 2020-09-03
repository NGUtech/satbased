<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Services;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Money\Service\PaymentServiceMap;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

final class ServicesResponder extends Responder
{
    private PaymentServiceMap $paymentServices;

    public function __construct(PaymentServiceMap $paymentServices)
    {
        $this->paymentServices = $paymentServices;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new JsonResponse($this->paymentServices->keys(), self::STATUS_OK);
    }
}
