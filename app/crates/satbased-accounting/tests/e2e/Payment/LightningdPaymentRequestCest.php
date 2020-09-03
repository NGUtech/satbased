<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\HttpCode;

class LightningdPaymentRequestCest
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
            'accepts' => '',
            'amount' => 1,
            'description' => 123
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'references' => ['Must be an array.'],
            'accepts' => ['Must be an array.'],
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

    public function requestPaymentAndRescan(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer2-verified');
        $I->sendPOST(self::URL_REQUEST_PATTERN, [
            'references' => ['someid' => 'someref'],
            'accepts' => ['testlightningd'],
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
        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testlightningd']);
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
        $I->runStack('alice', "payinvoice -f $paymentRequest");

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
            'accepts' => ['testlightningd'],
            'description' => 'payment description',
            'amount' => '100SAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '100000MSAT',
            'state' => 'requested'
        ]]);

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(sprintf(self::URL_SELECT_PATTERN, $paymentId), ['service' => 'testlightningd']);
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
        $I->runStack('alice', "payinvoice -f $paymentRequest");
        $I->runWorker('lightningd.adapter.messages', 'lightningd.adapter.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'settled']]);

        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '500100000MSAT']
        ]]);
    }
}
