<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Request;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Money\Validator\MoneyValidator;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\TextMapValidator;
use Daikon\Validize\Validator\TextValidator;
use Daikon\Validize\Validator\TimestampValidator;
use Daikon\Validize\Validator\ValidatorInterface;
use Daikon\ValueObject\TextList;
use Daikon\ValueObject\TextMap;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\Service\SatoshiCurrencies;
use Satbased\Accounting\Api\Account\AccountValidator;
use Satbased\Accounting\Api\Payment\AcceptablePaymentServiceValidator;
use Satbased\Accounting\Api\Payment\PaymentAction;

final class RequestAction extends PaymentAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        $requestPayment = $this->paymentService->request($payload);

        return $request->withResponder(
            [RequestResponder::class, [':requestPayment' => $requestPayment]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->error('description', TextValidator::class, ['required' => false])
            ->error('amount', MoneyValidator::class, [
                'convert' => SatoshiCurrencies::MSAT,
                'min' => '1MSAT',
                'provides' => 'valid_amount'
            ])->error('accepts', AcceptablePaymentServiceValidator::class, [
                'depends' => 'valid_amount',
                'required' => false,
                'import' => 'amount',
                'default' => TextList::makeEmpty()
            ])->error('expires', TimestampValidator::class, [
                'required' => false,
                'after' => 'now',
                'before' => '+6 months',
                'default' => Timestamp::makeEmpty()
            ])->critical('references', TextMapValidator::class, [
                'required' => false,
                'max' => 300,
                'size' => 5,
                'default' => TextMap::makeEmpty()
            ])->critical(JwtAuthenticator::AUTHENTICATOR, AccountValidator::class, [
                'export' => 'account'
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        return $this->authorizationService->isAllowed($role, $this, 'payment.request');
    }
}
