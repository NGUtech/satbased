<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Promote;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Security\Profile\Promote\PromoteProfile;

final class PromoteResponder extends Responder
{
    private PromoteProfile $promoteProfile;

    public function __construct(PromoteProfile $promoteProfile)
    {
        $this->promoteProfile = $promoteProfile;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new JsonResponse($this->promoteProfile->toNative(), self::STATUS_OK);
    }
}
