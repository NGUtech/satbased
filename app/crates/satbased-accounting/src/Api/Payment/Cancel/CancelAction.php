<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Cancel;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Accounting\Api\Payment\PaymentAction;
use Satbased\Accounting\Api\Payment\PaymentValidator;

final class CancelAction extends PaymentAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        $cancelPayment = $this->paymentService->cancel($payload['payment']);

        return $request->withResponder(
            [CancelResponder::class, [':cancelPayment' => $cancelPayment]]
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
        return $this->authorizationService->isAllowed($role, $resource, 'payment.cancel');
    }
}
