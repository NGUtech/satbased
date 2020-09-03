<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Make;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Money\Service\PaymentServiceInterface;
use Daikon\Money\Validator\MoneyValidator;
use Daikon\Money\Validator\PaymentServiceValidator;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\ChoiceValidator;
use Daikon\Validize\Validator\TextMapValidator;
use Daikon\Validize\Validator\TextValidator;
use Daikon\Validize\Validator\ValidatorInterface;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\TextMap;
use NGUtech\Bitcoin\Service\BitcoinServiceInterface;
use NGUtech\Bitcoin\Service\SatoshiCurrencies;
use NGUtech\Lightning\Service\LightningServiceInterface;
use Satbased\Accounting\Api\Account\AccountValidator;
use Satbased\Accounting\Api\Payment\BitcoinTransactionValidator;
use Satbased\Accounting\Api\Payment\LightningTransactionValidator;
use Satbased\Accounting\Api\Payment\PaymentAction;
use Satbased\Accounting\Payment\TransferService;

final class MakeAction extends PaymentAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        $makePayment = $this->paymentService->make($payload);

        return $request->withResponder(
            [MakeResponder::class, [':makePayment' => $makePayment]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->error('service', PaymentServiceValidator::class, ['provides' => 'valid_service'])
            ->silent('service', ChoiceValidator::class, [
                'export' => false,
                'choices' => $this->getServiceKeys(LightningServiceInterface::class),
                'depends' => 'valid_service',
                'provides' => 'lightning_tx'
            ])->silent('service', ChoiceValidator::class, [
                'export' => false,
                'choices' => $this->getServiceKeys(BitcoinServiceInterface::class),
                'depends' => 'valid_service',
                'provides' => 'bitcoin_tx'
            ])->silent('service', ChoiceValidator::class, [
                'export' => false,
                'choices' => $this->getServiceKeys(TransferService::class),
                'depends' => 'valid_service',
                'provides' => 'transfer_tx'
            ])
            ->error('description', TextValidator::class, ['required' => false, 'default' => Text::makeEmpty()])
            ->error('amount', MoneyValidator::class, [
                'provides' => 'valid_amount',
                'convert' => SatoshiCurrencies::MSAT,
                'min' => '1MSAT'
            ])->error('references', TextMapValidator::class, [
                'required' => false,
                'max' => 300,
                'default' => TextMap::makeEmpty()
            ])->critical(JwtAuthenticator::AUTHENTICATOR, AccountValidator::class, [
                'depends' => ['valid_service', 'valid_amount'],
                'export' => 'account',
                'status' => self::STATUS_NOT_FOUND,
                'provides' => 'valid_account'
            ])->critical('amount', AccountValidator::class, [
                'export' => false,
                'depends' => ['valid_account', 'valid_amount'],
                'import' => ['account', 'amount']
            ])->critical('transaction', BitcoinTransactionValidator::class, [
                'depends' => ['valid_service', 'valid_amount', 'bitcoin_tx'],
                'import' => ['service', 'amount']
            ])->critical('transaction', LightningTransactionValidator::class, [
                'depends' => ['valid_service', 'valid_amount', 'lightning_tx'],
                'import' => ['service', 'amount']
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        return $this->authorizationService->isAllowed($role, $this, 'payment.make');
    }

    private function getServiceKeys(string $interface): array
    {
        return $this->paymentServiceMap->filter(
            fn(string $key, PaymentServiceInterface $service): bool => $service instanceof $interface
        )->keys();
    }
}
