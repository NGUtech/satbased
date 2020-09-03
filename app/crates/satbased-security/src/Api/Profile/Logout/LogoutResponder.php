<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Logout;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Config\ConfigProviderInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Security\Api\Profile\HandlesAuthenticationCookies;

final class LogoutResponder extends Responder
{
    use HandlesAuthenticationCookies;

    private ConfigProviderInterface $config;

    public function __construct(ConfigProviderInterface $config)
    {
        $this->config = $config;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        //@todo check cookie expiration in postman
        return $this->expireAuthenticationCookies(new EmptyResponse);
    }
}
