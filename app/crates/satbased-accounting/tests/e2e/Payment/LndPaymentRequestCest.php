<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\HttpCode;

class LndPaymentRequestCest
{
    use ApiCestTrait;

    private const URL_REQUEST_PATTERN = '/payments/request';
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
        $I->seeResponseContainsJson(['errors' => [
            'amount' => ['Missing required input.']
        ]]);
    }

    public function requestPaymentWithInvalidParams(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => '',
            'amount' => 1,
            'description' => 123
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'references' => ['Must be an array.'],
            'amount' => ['Must be a string.'],
            'description' => ['Must be a string.']
        ]]);
    }

    public function requestPaymentWithNegativeAmount(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
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

    public function requestHoldPaymentAndCancel(ApiTester $I): void
    {
        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'description' => 'payment description',
            'amount' => '102SAT'
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '102000MSAT',
            'state' => 'requested'
        ]]);

        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testlnd.hold']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'selected']]);

        $paymentRequest = current($I->grabDataFromResponseByJsonPath('$._source.transaction.request'));
        $I->runStackAndDetach('bob', "payinvoice -f $paymentRequest");
        $I->runWorker('lnd.adapter.messages', 'lnd.adapter.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'received']]);

        $I->sendPOST("/payments/$paymentId/cancel");
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->runWorker('lnd.adapter.messages', 'lnd.adapter.message_queue');

        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'cancelled']]);

        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '1000000000MSAT']
        ]]);
    }

    public function requestHoldPaymentWithCltvExpiry(ApiTester $I): void
    {
        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'description' => 'payment description',
            'amount' => '103SAT'
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '103000MSAT',
            'state' => 'requested'
        ]]);

        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testlnd.hold']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'selected']]);

        $paymentRequest = current($I->grabDataFromResponseByJsonPath('$._source.transaction.request'));
        $I->runStackAndDetach('bob', "payinvoice -f $paymentRequest");
        $I->runWorker('lnd.adapter.messages', 'lnd.adapter.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'received']]);

        $I->runStack('bitcoin', 'generate 16');
        $I->runWorker('bitcoind.adapter.messages', 'bitcoind.adapter.message_queue');
        $I->runWorker('lnd.adapter.messages', 'lnd.adapter.message_queue');

        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'cancelled']]);

        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '1000000000MSAT']
        ]]);
    }

    public function requestPaymentAndRescan(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer2-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => ['someid' => 'someref'],
            'accepts' => ['testlnd'],
            'description' => 'payment description',
            'amount' => '110SAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '110000MSAT',
            'state' => 'requested'
        ]]);

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testlnd']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'transaction' => [
                'label' => $paymentId,
                'description' => 'payment description'
            ],
            'state' => 'selected'
        ]]);

        $paymentRequest = current($I->grabDataFromResponseByJsonPath('$._source.transaction.request'));
        $I->runStack('bob', "payinvoice -f $paymentRequest");

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
            'wallet' => ['MSAT' => '110000MSAT']
        ]]);
    }

    public function requestPaymentAndSettle(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '100001MSAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '100001MSAT',
            'state' => 'requested'
        ]]);

        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testlnd']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'selected']]);

        $paymentRequest = current($I->grabDataFromResponseByJsonPath('$._source.transaction.request'));
        $I->runStack('bob', "payinvoice -f $paymentRequest");
        $I->runWorker('lnd.adapter.messages', 'lnd.adapter.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'settled']]);

        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '500100001MSAT']
        ]]);
    }

    public function requestHoldPaymentAndSettle(ApiTester $I): void
    {
        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'description' => 'payment description',
            'amount' => '101001MSAT'
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '101001MSAT',
            'state' => 'requested'
        ]]);

        //@todo change user
        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testlnd.hold']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'transaction' => [
                'label' => $paymentId,
                'description' => 'payment description'
            ],
            'state' => 'selected'
        ]]);

        $paymentRequest = current($I->grabDataFromResponseByJsonPath('$._source.transaction.request'));
        $I->runStackAndDetach('bob', "payinvoice -f $paymentRequest");
        $I->runWorker('lnd.adapter.messages', 'lnd.adapter.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'received']]);

        //@todo change user back
        $I->sendPOST("/payments/$paymentId/settle");
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->runWorker('lnd.adapter.messages', 'lnd.adapter.message_queue');

        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'settled']]);

        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '1000101001MSAT']
        ]]);
    }
}
