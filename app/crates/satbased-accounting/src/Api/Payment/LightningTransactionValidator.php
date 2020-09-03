<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment;

use Daikon\Interop\Assertion;
use Daikon\Interop\InvalidArgumentException;
use Daikon\Money\Exception\PaymentServiceException;
use Daikon\Validize\Validator\Validator;
use Daikon\ValueObject\FloatValue;
use Daikon\ValueObject\Timestamp;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use NGUtech\Lightning\Entity\LightningInvoice;
use NGUtech\Lightning\Entity\LightningPayment;
use NGUtech\Lightning\Service\LightningServiceInterface;
use NGUtech\Lightning\ValueObject\Request;
use Satbased\Accounting\ValueObject\Transaction;

final class LightningTransactionValidator extends Validator
{
    private const DEFAULT_FEE_LIMIT = 0.5;
    private const MIN_FEE_LIMIT = 0;
    private const MAX_FEE_LIMIT = 10;
    private const MIN_FEE = '0MSAT';

    /** @param mixed $input */
    protected function validate($input): Transaction
    {
        $imports = $this->getImports();
        $settings = $this->getSettings();

        $defaultFeeLimit = $settings['defaultFeeLimit'] ?? self::DEFAULT_FEE_LIMIT;
        $minFeeLimit = $settings['minFeeLimit'] ?? self::MIN_FEE_LIMIT;
        $maxFeeLimit = $settings['maxFeeLimit'] ?? self::MAX_FEE_LIMIT;
        $minFee = $settings['minFee'] ?? self::MIN_FEE;

        Assertion::isInstanceOf($imports['service'], LightningServiceInterface::class, 'Service is not validated.');
        Assertion::isInstanceOf($imports['amount'], Bitcoin::class, 'Amount is not validated.');
        Assertion::true($imports['service']->canSend($imports['amount']), 'Payment service cannot send given amount.');
        //@todo support destination instead of request for keysend
        Assertion::keyExists($input, 'request', "'request' is required.");
        Assertion::notBlank($input['request'], "'request' must not be empty.");

        try {
            /** @var LightningInvoice $invoice */
            $invoice = $imports['service']->decode(Request::fromNative($input['request']));
            $input['destination'] = (string)$invoice->getDestination();
        } catch (PaymentServiceException $error) {
            throw new InvalidArgumentException('Invalid payment request.');
        }
        Assertion::false($invoice->hasExpired(Timestamp::now()->modify('+1 minute')), 'Payment request has expired.');
        Assertion::true($invoice->getAmount()->equals($imports['amount']), 'Payment request amount mismatch.');

        if (array_key_exists('feeLimit', $input)) {
            Assertion::numeric($input['feeLimit'], "'feeLimit' must be a number.");
            Assertion::between($input['feeLimit'], $minFeeLimit, $maxFeeLimit, sprintf(
                "'feeLimit' must be between %s and %s.",
                number_format($minFeeLimit, 2),
                number_format($maxFeeLimit, 2)
            ));
        }

        $input['@type'] = LightningPayment::class;
        $input['amount'] = (string)$imports['amount'];
        $input['feeLimit'] = FloatValue::fromNative($input['feeLimit'] ?? $defaultFeeLimit)->toNative();
        try {
            $feeEstimate = $imports['service']->estimateFee(LightningPayment::fromNative($input));
        } catch (PaymentServiceException $error) {
            throw new InvalidArgumentException('Cannot estimate route fee.');
        }
        Assertion::true(
            $feeEstimate->isGreaterThanOrEqual(Bitcoin::fromNative($minFee)),
            'Fee estimate is below expected minimum fee.'
        );

        $maxFee = $invoice->getAmount()->percentage($input['feeLimit'], Bitcoin::ROUND_UP);
        Assertion::true($maxFee->isGreaterThanOrEqual($feeEstimate), 'Fee estimate is greater than allowed limit.');
        $input['feeEstimate'] = (string)$feeEstimate;

        return Transaction::fromNative($input);
    }
}
