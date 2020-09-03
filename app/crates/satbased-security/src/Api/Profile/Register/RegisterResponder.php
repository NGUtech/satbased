<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Register;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Security\Profile\Register\RegisterProfile;

final class RegisterResponder extends Responder
{
    private RegisterProfile $registerProfile;

    public function __construct(RegisterProfile $registerProfile)
    {
        $this->registerProfile = $registerProfile;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new JsonResponse($this->registerProfile->toNative(), self::STATUS_CREATED);
    }
}
