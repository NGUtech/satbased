<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Login;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Config\ConfigProviderInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Security\Profile\Login\LoginProfile;
use Satbased\Security\Api\Profile\HandlesAuthenticationCookies;

final class LoginResponder extends Responder
{
    use HandlesAuthenticationCookies;

    private ConfigProviderInterface $config;

    private LoginProfile $loginProfile;

    private string $jwt;

    public function __construct(ConfigProviderInterface $config, LoginProfile $loginProfile, string $jwt)
    {
        $this->config = $config;
        $this->loginProfile = $loginProfile;
        $this->jwt = $jwt;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return $this->setAuthenticationCookies(
            new JsonResponse($this->loginProfile->toNative(), self::STATUS_OK)
        );
    }
}
