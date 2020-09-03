<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Verify;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Security\Profile\Verify\VerifyProfile;

final class VerifyResponder extends Responder
{
    private VerifyProfile $verifyProfile;

    public function __construct(VerifyProfile $verifyProfile)
    {
        $this->verifyProfile = $verifyProfile;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new JsonResponse($this->verifyProfile->toNative(), self::STATUS_OK);
    }
}
