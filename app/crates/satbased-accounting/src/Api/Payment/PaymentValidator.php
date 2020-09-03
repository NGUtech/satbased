<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment;

use Daikon\Interop\Assertion;
use Daikon\Validize\Validator\Validator;
use Satbased\Accounting\ReadModel\Standard\Payment;
use Satbased\Accounting\ReadModel\Standard\PaymentCollection;
use Satbased\Accounting\ValueObject\PaymentId;

final class PaymentValidator extends Validator
{
    private PaymentCollection $paymentCollection;

    public function __construct(PaymentCollection $paymentCollection)
    {
        $this->paymentCollection = $paymentCollection;
    }

    /** @param mixed $input */
    protected function validate($input): Payment
    {
        Assertion::regex($input, PaymentId::PATTERN, 'Invalid format.');

        $payment = $this->paymentCollection->byId($input)->getFirst();
        Assertion::isInstanceOf($payment, Payment::class, 'Not found.');

        return $payment;
    }
}
