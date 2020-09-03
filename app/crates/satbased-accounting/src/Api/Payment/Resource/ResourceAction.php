<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Resource;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Accounting\Api\Payment\PaymentAction;
use Satbased\Accounting\Api\Payment\PaymentValidator;

final class ResourceAction extends PaymentAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        return $request->withResponder(
            [ResourceResponder::class, [':payment' => $payload['payment']]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->critical('paymentId', PaymentValidator::class, [
                'export' => 'payment',
                'status' => self::STATUS_NOT_FOUND
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        $resource = $request->getPayload()['payment'] ?? $this;
        return $this->authorizationService->isAllowed($role, $resource, 'payment.resource');
    }
}
