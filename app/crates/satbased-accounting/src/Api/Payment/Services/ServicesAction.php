<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Services;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Accounting\Api\Account\AccountValidator;
use Satbased\Accounting\Api\Payment\PaymentAction;
use Satbased\Accounting\Api\Payment\PaymentValidator;

final class ServicesAction extends PaymentAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        $paymentServices = $this->paymentService->services($payload['payment'], $payload['account']);

        return $request->withResponder(
            [ServicesResponder::class, [':paymentServices' => $paymentServices]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->critical('paymentId', PaymentValidator::class, [
                'export' => 'payment',
                'status' => self::STATUS_NOT_FOUND
            ])->critical(JwtAuthenticator::AUTHENTICATOR, AccountValidator::class, [
                'export' => 'account',
                'status' => self::STATUS_NOT_FOUND
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        return $this->authorizationService->isAllowed($role, $this, 'payment.services');
    }
}
