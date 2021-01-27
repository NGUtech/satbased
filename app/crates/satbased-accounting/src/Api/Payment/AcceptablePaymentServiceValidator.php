<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment;

use Daikon\Interop\Assert;
use Daikon\Interop\Assertion;
use Daikon\Money\Service\PaymentServiceMap;
use Daikon\Money\ValueObject\MoneyInterface;
use Daikon\Validize\Validator\Validator;
use Daikon\ValueObject\Text;
use Daikon\ValueObject\TextList;
use Satbased\Accounting\ReadModel\Standard\Payment;

final class AcceptablePaymentServiceValidator extends Validator
{
    private PaymentServiceMap $paymentServiceMap;

    public function __construct(PaymentServiceMap $paymentServiceMap)
    {
        $this->paymentServiceMap = $paymentServiceMap;
    }

    /** @param mixed $input */
    protected function validateAccepts($input): TextList
    {
        $imports = $this->getImports();
        Assertion::isInstanceOf($imports['amount'], MoneyInterface::class, 'Amount is not validated.');

        $availableServices = $this->paymentServiceMap->availableForRequest($imports['amount']);

        Assert::that($input, 'Invalid services.')
            ->isArray('Must be an array.')
            ->notEmpty('Must not be empty.')
            ->all()
            ->string()
            ->notBlank()
            ->satisfy([$availableServices, 'has'], 'Unacceptable service.');

        return TextList::fromNative($input);
    }

    /** @param mixed $input */
    protected function validateService($input): void
    {
        $imports = $this->getImports();
        Assertion::isInstanceOf($imports['payment'], Payment::class, 'Payment is not validated.');

        $amount = $imports['payment']->getAmount();
        $availableServices = $this->paymentServiceMap->availableForRequest($amount);
        $acceptedServices = $imports['payment']->getAccepts();
        if (!$acceptedServices->isEmpty()) {
            $availableServices = $availableServices->filter(
                fn (string $key): bool => $acceptedServices->find(Text::fromNative($key)) !== false
            );
        }

        Assertion::true($availableServices->has($input) !== false, 'Unacceptable service.');
    }
}
