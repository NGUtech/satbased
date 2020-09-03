<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Close;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Config\ConfigProviderInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Security\Api\Profile\HandlesAuthenticationCookies;
use Satbased\Security\Profile\Close\CloseProfile;

final class CloseResponder extends Responder
{
    use HandlesAuthenticationCookies;

    private ConfigProviderInterface $config;

    private CloseProfile $closeProfile;

    public function __construct(ConfigProviderInterface $config, CloseProfile $closeProfile)
    {
        $this->config = $config;
        $this->closeProfile = $closeProfile;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return $this->expireAuthenticationCookies(
            new JsonResponse($this->closeProfile->toNative(), self::STATUS_OK)
        );
    }
}
