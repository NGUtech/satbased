<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Promote;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Security\Api\Profile\ProfileValidator;
use Satbased\Security\Api\Profile\ProfileAction;
use Satbased\Security\Api\Profile\RoleValidator;

final class PromoteAction extends ProfileAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        $promoteProfile = $this->profileService->promote($payload['profile'], $payload['role']);

        return $request->withResponder(
            [PromoteResponder::class, [':promoteProfile' => $promoteProfile]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->critical('role', RoleValidator::class)
            ->critical('profileId', ProfileValidator::class, [
                'export' => 'profile',
                'status' => self::STATUS_NOT_FOUND
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        $resource = $request->getPayload()['profile'] ?? $this;
        return $this->authorizationService->isAllowed($role, $resource, 'profile.promote');
    }
}
