<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Resource;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Security\Api\Profile\ProfileAction;
use Satbased\Security\Api\Profile\ProfileValidator;

final class ResourceAction extends ProfileAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        return $request->withResponder(
            [ResourceResponder::class, [':profile' => $payload['profile']]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->critical('profileId', ProfileValidator::class, [
                'export' => 'profile',
                'status' => self::STATUS_NOT_FOUND
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        $resource = $request->getPayload()['profile'] ?? $this;
        return $this->authorizationService->isAllowed($role, $resource, 'profile.resource');
    }
}
