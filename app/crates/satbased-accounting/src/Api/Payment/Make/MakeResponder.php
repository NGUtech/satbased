<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Make;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Accounting\Payment\Make\MakePayment;

final class MakeResponder extends Responder
{
    private MakePayment $makePayment;

    public function __construct(MakePayment $makePayment)
    {
        $this->makePayment = $makePayment;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new JsonResponse($this->makePayment->toNative(), self::STATUS_CREATED);
    }
}
