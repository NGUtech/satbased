<?php declare(strict_types=1);

namespace Satbased\Accounting\Fixture\Test\Payment;

use Daikon\Boot\Fixture\Fixture;
use Daikon\Elasticsearch7\Query\TermFilter;
use Daikon\Metadata\Metadata;
use Satbased\Accounting\ReadModel\Standard\Account;
use Satbased\Accounting\ReadModel\Standard\AccountCollection;

final class ImportTestPayment20200513140000 extends Fixture
{
    private AccountCollection $accountCollection;

    public function __construct(AccountCollection $accountCollection)
    {
        $this->accountCollection = $accountCollection;
    }

    protected function import(): void
    {
        foreach ($this->loadFile('test-payment-data.json') as $fixture) {
            if (isset($fixture['values']['profileId'])) {
                $fixture['values']['accountId'] = $this->getAccountId($fixture['values']['profileId']);
            }
            $command = $fixture['@type']::fromNative($fixture['values']);
            $metadata = Metadata::fromNative($fixture['metadata'] ?? []);
            $this->publish($command, $metadata);
        }
    }

    private function loadFile(string $filename): array
    {
        return json_decode(file_get_contents(__DIR__."/$filename"), true);
    }

    private function getAccountId($profileId): string
    {
        /** @var Account $account */
        $account = $this->accountCollection->selectOne(
            TermFilter::fromNative(['profileId' => $profileId])
        )->getFirst();

        return (string)$account->getAccountId();
    }
}
