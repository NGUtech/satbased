<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment;

use Daikon\Interop\Assertion;
use Daikon\Interop\InvalidArgumentException;
use Daikon\Money\Exception\PaymentServiceException;
use Daikon\Validize\Validator\Validator;
use Daikon\ValueObject\FloatValue;
use NGUtech\Bitcoin\Entity\BitcoinTransaction;
use NGUtech\Bitcoin\Service\BitcoinServiceInterface;
use NGUtech\Bitcoin\ValueObject\Address;
use NGUtech\Bitcoin\ValueObject\Bitcoin;
use Satbased\Accounting\ValueObject\Transaction;

final class BitcoinTransactionValidator extends Validator
{
    private const DEFAULT_FEE_RATE = 0.00002;
    private const MIN_FEE_RATE = 0.00001;
    private const MAX_FEE_RATE = 0.0005;
    private const MIN_FEE = '150000MSAT';

    /** @param mixed $input */
    protected function validate($input): Transaction
    {
        $imports = $this->getImports();
        $settings = $this->getSettings();

        $defaultFeeRate = $settings['defaultFeeRate'] ?? self::DEFAULT_FEE_RATE;
        $minFeeRate = $settings['minFeeRate'] ?? self::MIN_FEE_RATE;
        $maxFeeRate = $settings['maxFeeRate'] ?? self::MAX_FEE_RATE;
        $minFee = $settings['minFee'] ?? self::MIN_FEE;

        Assertion::isInstanceOf($imports['service'], BitcoinServiceInterface::class, 'Service is not acceptable.');
        Assertion::isInstanceOf($imports['amount'], Bitcoin::class, 'Amount is not validated.');

        /** @var BitcoinServiceInterface $service */
        $service = $imports['service'];
        /** @var Bitcoin $amount */
        $amount = $imports['amount'];

        Assertion::eq(0, bcmod($amount->getAmount(), '1000'), 'Amount must be rounded to nearest SAT.');
        Assertion::true($service->canSend($amount), 'Payment service cannot send given amount.');
        Assertion::keyExists($input, 'address', "'address' is required.");
        Assertion::notBlank($input['address'], "'address' must not be empty.");
        Assertion::true($service->validateAddress(Address::fromNative($input['address'])), 'Invalid address.');

        if (array_key_exists('feeRate', $input)) {
            Assertion::float($input['feeRate'], "'feeRate' must be a decimal.");
            Assertion::between($input['feeRate'], $minFeeRate, $maxFeeRate, sprintf(
                "'feeRate' must be between %s and %s.",
                number_format($minFeeRate, 8),
                number_format($maxFeeRate, 8)
            ));
        }

        $input['@type'] = BitcoinTransaction::class;
        $input['amount'] = (string)$amount;
        $input['outputs'] = [['address' => $input['address'], 'value' => $input['amount']]];
        $input['feeRate'] = FloatValue::fromNative($input['feeRate'] ?? $defaultFeeRate)->toNative();
        $input['confTarget'] = 3; //@todo configurable conf target
        try {
            $feeEstimate = $service->estimateFee(BitcoinTransaction::fromNative($input));
        } catch (PaymentServiceException $error) {
            throw new InvalidArgumentException('Cannot estimate fee.');
        }
        //@todo add a maxfee check
        Assertion::true(
            $feeEstimate->isGreaterThanOrEqual(Bitcoin::fromNative($minFee)),
            "Fee rate is too low for minimum $minFee fee."
        );
        $input['feeEstimate'] = (string)$feeEstimate;

        return Transaction::fromNative($input);
    }
}
