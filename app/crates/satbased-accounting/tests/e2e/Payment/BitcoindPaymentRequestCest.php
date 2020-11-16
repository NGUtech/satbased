<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\HttpCode;

class BitcoindPaymentRequestCest
{
    use ApiCestTrait;

    private const URL_REQUEST_PATTERN = '/payments/request';
    private const URL_SERVICES_PATTERN = '/payments/%s/services';
    private const URL_SELECT_PATTERN = '/payments/%s/select';
    private const URL_RESCAN_PATTERN = '/payments/%s/rescan';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->bootstrap();
        $I->setup();
    }

    public function postWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendPOST(self::URL_REQUEST_PATTERN);
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function requestPaymentWithoutParams(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['amount' => ['Missing required input.']]]);
    }

    public function requestPaymentWithInvalidParams(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => '',
            'accepts' => [],
            'amount' => 1,
            'description' => 123
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'references' => ['Must be an array.'],
            'accepts' => ['Must not be empty.'],
            'amount' => ['Must be a string.'],
            'description' => ['Must be a string.']
        ]]);
    }

    public function requestPaymentWithNegativeAmount(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'description' => 'payment description',
            'amount' => '-100SAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'amount' => ['Amount must be at least 1MSAT.']
        ]]);
    }

    public function requestPaymentWithUnknownAccepts(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => ['someid' => 'someref'],
            'accepts' => ['unknown'],
            'description' => 'payment description',
            'amount' => '1000SAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['accepts' => ['Unknown service.']]]);
    }

    public function requestPaymentAndSelectUnacceptableService(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => ['someid' => 'someref'],
            'accepts' => ['testbitcoind'],
            'description' => 'payment description',
            'amount' => '1000SAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '1000000MSAT',
            'state' => 'requested'
        ]]);

        $this->loginProfile($I, 'customer-verified');
        $I->sendGET(sprintf(self::URL_SERVICES_PATTERN, $paymentId));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['testbitcoind']);

        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testlightningd']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['service' => ['Unacceptable service.']]]);
    }

    public function requestPaymentAndRescan(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer2-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '1100000MSAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '1100000MSAT',
            'state' => 'requested'
        ]]);

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testbitcoind']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'transaction' => [
                'outputs' => [['value' => '1100000MSAT']],
                'label' => $paymentId,
                'comment' => 'payment description'
            ],
            'state' => 'selected'
        ]]);

        $paymentAddress = current($I->grabDataFromResponseByJsonPath('$._source.transaction.outputs.0.address'));
        $I->runStack('bitcoin', "sendtoaddress $paymentAddress 0.000011");
        $I->runStack('bitcoin', 'generate 3');

        $this->loginProfile($I, 'customer2-verified');
        $I->sendGET(sprintf(self::URL_RESCAN_PATTERN, $paymentId));
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->seeResponseEquals(null);

        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'settled']]);

        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '1100000MSAT']
        ]]);
    }

    public function requestPaymentAndSettle(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '1000000MSAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '1000000MSAT',
            'state' => 'requested'
        ]]);

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testbitcoind']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'transaction' => [
                'outputs' => [['value' => '1000000MSAT']],
                'label' => $paymentId,
                'comment' => 'payment description',
            ],
            'state' => 'selected'
        ]]);

        $paymentAddress = current($I->grabDataFromResponseByJsonPath('$._source.transaction.outputs.0.address'));
        $I->runStack('bitcoin', "sendtoaddress $paymentAddress 0.00001");
        $I->runStack('bitcoin', 'generate 3');
        $I->runWorker('bitcoind.adapter.messages', 'bitcoind.adapter.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'settled']]);

        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '501000000MSAT']
        ]]);
    }

    /**
     * @depends requestPaymentAndSettle
     */
    public function requestPaymentAndOverpay(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '5000000MSAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '5000000MSAT',
            'state' => 'requested'
        ]]);

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testbitcoind']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'transaction' => [
                'amount' => '5000000MSAT',
                'outputs' => [['value' => '5000000MSAT']],
                'label' => $paymentId,
                'comment' => 'payment description',
            ],
            'state' => 'selected'
        ]]);

        $paymentAddress = current($I->grabDataFromResponseByJsonPath('$._source.transaction.outputs.0.address'));
        $I->runStack('bitcoin', "sendtoaddress $paymentAddress 0.00006");
        $I->runStack('bitcoin', 'generate 3');
        $I->runWorker('bitcoind.adapter.messages', 'bitcoind.adapter.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'settled']]);

        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '507000000MSAT']
        ]]);

        //check payment/transaction amounts
    }
}
