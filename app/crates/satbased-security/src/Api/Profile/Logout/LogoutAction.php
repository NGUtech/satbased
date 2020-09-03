<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Logout;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtDecoder;
use Satbased\Security\Api\Profile\ProfileAction;

final class LogoutAction extends ProfileAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $cookiesConfig = $this->config->get('project.authentication.cookies', []);
        $jwtAttribute = $cookiesConfig['jwt']['attribute'] ?? JwtDecoder::DEFAULT_ATTR_JWT;

        if ($jwt = $request->getAttribute($jwtAttribute)) {
            $this->profileService->logout($jwt->uid);
        }

        return $request->withResponder(
            [LogoutResponder::class, []]
        );
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        return true;
    }

    public function isSecure(): bool
    {
        return false;
    }
}
