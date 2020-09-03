<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Account\Resource;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Accounting\Api\Account\AccountAction;
use Satbased\Accounting\Api\Account\AccountValidator;

final class ResourceAction extends AccountAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        return $request->withResponder(
            [ResourceResponder::class, [':account' => $payload['account']]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->critical('accountId', AccountValidator::class, [
                'export' => 'account',
                'status' => self::STATUS_NOT_FOUND
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        $resource = $request->getPayload()['account'] ?? $this;
        return $this->authorizationService->isAllowed($role, $resource, 'account.resource');
    }
}
