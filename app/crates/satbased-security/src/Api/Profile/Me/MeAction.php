<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Me;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtAuthenticator;
use Satbased\Security\Api\Profile\ProfileAction;

final class MeAction extends ProfileAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $profile = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);

        return $request->withResponder(
            [MeResponder::class, [':profile' => $profile]]
        );
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        $resource = $request->getPayload()['profile'] ?? $this;
        return $this->authorizationService->isAllowed($role, $resource, 'profile.resource');
    }
}
