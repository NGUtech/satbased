<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\HttpCode;

class BitcoindPaymentMakeCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/payments/make';
    private const URL_CANCEL_PATTERN = '/payments/%s/cancel';
    private const URL_RESCAN_PATTERN = '/payments/%s/rescan';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->bootstrap();
        $I->setup();
    }

    public function postWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendPOST(self::URL_PATTERN);
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function makePaymentWithoutParams(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'service' => ['Missing required input.'],
            'amount' => ['Missing required input.']
        ]]);
    }

    public function makePaymentWithInvalidParams(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'abc',
            'references' => '',
            'amount' => '1',
            'description' => 123,
            'transaction' => 'xyz'
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'service' => ['Unknown service.'],
            'references' => ['Must be an array.'],
            'amount' => ['Invalid amount.'],
            'description' => ['Must be a string.']
        ]]);
    }

    public function makePaymentWithNegativeAmount(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testbitcoind',
            'description' => 'payment description',
            'amount' => '-100SAT'
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'amount' => ['Amount must be at least 1MSAT.']
        ]]);
    }

    public function makePaymentWithNonSatAmount(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testbitcoind',
            'description' => 'payment description',
            'amount' => '1234010MSAT',
            'transaction' => ['address' => 'mwF1rmTrDH2pJNyRdQrWbWGv5UHeq5xUVq']
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'transaction' => ['Amount must be rounded to nearest SAT.']
        ]]);
    }

    public function makePaymentUnderMinimiumAmount(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testbitcoind',
            'description' => 'payment description',
            'amount' => '1SAT',
            'transaction' => ['address' => 'mwF1rmTrDH2pJNyRdQrWbWGv5UHeq5xUVq']
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'transaction' => ['Payment service cannot send given amount.']
        ]]);
    }

    public function makePaymentAndCancel(ApiTester $I): void
    {
        $I->runStack('bitcoin', 'getnewaddress "receiving" legacy');
        $address = $I->getOutputOfStackCommand();

        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testbitcoind',
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '5000SAT',
            'transaction' => ['address' => $address]
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_MADE_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'service' => 'testbitcoind',
            'amount' => '5000000MSAT',
            'transaction' => [
                'amount' => '5000000MSAT',
                'outputs' => [['address' => $address, 'value' => '5000000MSAT']],
                'label' => $paymentId
            ],
            'state' => 'made'
        ]]);

        $feeEstimate = current($I->grabDataFromResponseByJsonPath('$._source.transaction.feeEstimate'));
        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => 1000000000-5000000-intval($feeEstimate).'MSAT']
        ]]);

        $I->sendPOST(sprintf(self::URL_CANCEL_PATTERN, $paymentId));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');

        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_MADE_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'state' => 'cancelled'
        ]]);

        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '1000000000MSAT']
        ]]);
    }

    public function makePaymentAndRescan(ApiTester $I): void
    {
        $I->runStack('bitcoin', 'getnewaddress "receiving"');
        $address = $I->getOutputOfStackCommand();

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testbitcoind',
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '5000sat',
            'transaction' => ['address' => $address]
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_MADE_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'service' => 'testbitcoind',
            'amount' => '5000000MSAT',
            'transaction' => [
                'amount' => '5000000MSAT',
                'outputs' => [['address' => $address, 'value' => '5000000MSAT']],
                'feeRate' => 0.00002,
                'confTarget' => 3,
                'label' => $paymentId
            ],
            'state' => 'made'
        ]]);

        $feeEstimate = current($I->grabDataFromResponseByJsonPath('$._source.transaction.feeEstimate'));
        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => 500000000-5000000-intval($feeEstimate).'MSAT']
        ]]);

        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getPayment($paymentId);
        $feeSettled = current($I->grabDataFromResponseByJsonPath('$._source.transaction.feeSettled'));
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '5000000MSAT',
            'transaction' => [
                //'id => 'something',
                'amount' => '5000000MSAT',
                'outputs' => [['address' => $address, 'value' => '5000000MSAT']],
                'confTarget' => 3,
                'feeEstimate' => $feeEstimate,
                'feeSettled' => $feeSettled
            ],
            'state' => 'sent'
        ]]);

        $I->runStack('bitcoin', 'generate 1');
        $I->sendGET(sprintf(self::URL_RESCAN_PATTERN, $paymentId));
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->seeResponseEquals(null);
        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'sent']]);

        $I->runStack('bitcoin', 'generate 2');
        $I->sendGET(sprintf(self::URL_RESCAN_PATTERN, $paymentId));
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->seeResponseEquals(null);
        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '5000000MSAT',
            'state' => 'completed'
        ]]);

        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => 500000000-5000000-intval($feeSettled).'MSAT']
        ]]);
    }

    public function makePaymentAndComplete(ApiTester $I): void
    {
        $I->runStack('bitcoin', 'getnewaddress "receiving" legacy');
        $address = $I->getOutputOfStackCommand();

        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testbitcoind',
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '5000SAT',
            'transaction' => [
                'address' => $address,
                'feeRate' => 0.000015
            ]
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_MADE_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'service' => 'testbitcoind',
            'amount' => '5000000MSAT',
            'transaction' => [
                'amount' => '5000000MSAT',
                'outputs' => [['address' => $address, 'value' => '5000000MSAT']],
                'feeRate' => 0.000015,
                'confTarget' => 3,
                'label' => $paymentId
            ],
            'state' => 'made'
        ]]);

        $feeEstimate = current($I->grabDataFromResponseByJsonPath('$._source.transaction.feeEstimate'));
        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => 1000000000-5000000-intval($feeEstimate).'MSAT']
        ]]);

        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getPayment($paymentId);
        $feeSettled = current($I->grabDataFromResponseByJsonPath('$._source.transaction.feeSettled'));
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '5000000MSAT',
            'transaction' => [
                //'id => 'something',
                'amount' => '5000000MSAT',
                'outputs' => [['address' => $address, 'value' => '5000000MSAT']],
                'confTarget' => 3,
                'feeEstimate' => $feeEstimate,
                'feeSettled' => $feeSettled
            ],
            'state' => 'sent'
        ]]);

        $I->runStack('bitcoin', 'generate 1');
        $I->runWorker('bitcoind.adapter.messages', 'bitcoind.adapter.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'sent']]);

        $I->runStack('bitcoin', 'generate 2');
        $I->runWorker('bitcoind.adapter.messages', 'bitcoind.adapter.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '5000000MSAT',
            'state' => 'completed'
        ]]);

        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => 1000000000-5000000-intval($feeSettled).'MSAT']
        ]]);
    }

    // @todo add a send retry test
    // @todo add a send mempool flush test
}
