<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Approve;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Accounting\Payment\Approve\ApprovePayment;

final class ApproveResponder extends Responder
{
    private ApprovePayment $approvePayment;

    public function __construct(ApprovePayment $approvePayment)
    {
        $this->approvePayment = $approvePayment;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new JsonResponse($this->approvePayment->toNative(), self::STATUS_OK);
    }
}
