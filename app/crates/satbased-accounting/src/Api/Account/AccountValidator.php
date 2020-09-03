<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Account;

use Daikon\Interop\Assertion;
use Daikon\Elasticsearch7\Query\TermFilter;
use Daikon\Money\ValueObject\MoneyInterface;
use Daikon\Validize\Validator\Validator;
use Satbased\Accounting\ReadModel\Standard\Account;
use Satbased\Accounting\ReadModel\Standard\AccountCollection;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Security\ReadModel\Standard\Profile;

final class AccountValidator extends Validator
{
    private AccountCollection $accountCollection;

    public function __construct(AccountCollection $accountCollection)
    {
        $this->accountCollection = $accountCollection;
    }

    /** @param mixed $input */
    protected function validate($input): Account
    {
        Assertion::regex($input, AccountId::PATTERN, 'Invalid format.');

        $account = $this->accountCollection->byId($input)->getFirst();
        Assertion::isInstanceOf($account, Account::class, 'Not found.');

        return $account;
    }

    /** @param mixed $input */
    protected function validateAuthenticator($input): Account
    {
        Assertion::isInstanceOf($input, Profile::class, 'Invalid profile.');

        // assumption there is only one account per profile
        $account = $this->accountCollection->selectOne(
            TermFilter::fromNative(['profileId' => (string)$input->getProfileId()])
        )->getFirst();

        Assertion::isInstanceOf($account, Account::class, 'Not found.');

        return $account;
    }

    /** @param mixed $input */
    protected function validateAmount($input): void
    {
        $imports = $this->getImports();
        Assertion::isInstanceOf($imports['amount'], MoneyInterface::class, 'Amount is not validated.');
        Assertion::isInstanceOf($imports['account'], Account::class, 'Account is not validated.');
        Assertion::true($imports['account']->getWallet()->hasBalance($imports['amount']), 'Insufficient balance.');
    }
}
