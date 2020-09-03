<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Select;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Accounting\Payment\Select\SelectPayment;

final class SelectResponder extends Responder
{
    private SelectPayment $selectPayment;

    public function __construct(SelectPayment $selectPayment)
    {
        $this->selectPayment = $selectPayment;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new JsonResponse($this->selectPayment->toNative(), self::STATUS_OK);
    }
}
