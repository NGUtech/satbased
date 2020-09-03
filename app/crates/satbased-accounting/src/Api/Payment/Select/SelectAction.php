<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Select;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Money\Validator\PaymentServiceValidator;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Accounting\Api\Account\AccountValidator;
use Satbased\Accounting\Api\Payment\AcceptablePaymentServiceValidator;
use Satbased\Accounting\Api\Payment\PaymentAction;
use Satbased\Accounting\Api\Payment\PaymentValidator;

final class SelectAction extends PaymentAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        $selectPayment = $this->paymentService->select($payload);

        return $request->withResponder(
            [SelectResponder::class, [':selectPayment' => $selectPayment]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->critical('service', PaymentServiceValidator::class)
            ->critical('paymentId', PaymentValidator::class, [
                'export' => 'payment',
                'status' => self::STATUS_NOT_FOUND
            ])
            ->critical('service', AcceptablePaymentServiceValidator::class, [
                'export' => false,
                'import' => 'payment'
            ])
            ->critical(JwtAuthenticator::AUTHENTICATOR, AccountValidator::class, [
                'export' => 'account',
                'status' => self::STATUS_NOT_FOUND
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        $resource = $request->getPayload()['payment'] ?? $this;
        return $this->authorizationService->isAllowed($role, $resource, 'payment.select');
    }
}
