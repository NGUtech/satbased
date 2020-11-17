<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\HttpCode;

class TransferPaymentRequestCest
{
    use ApiCestTrait;

    private const REQUEST_URL_PATTERN = '/payments/request';
    private const SELECT_URL_PATTERN = '/payments/%s/select';
    private const CANCEL_URL_PATTERN = '/payments/%s/cancel';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->bootstrap();
        $I->setup();
    }

    public function postWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendPOST(self::REQUEST_URL_PATTERN);
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function requestPaymentWithoutParams(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::REQUEST_URL_PATTERN);
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
        $I->sendPOST(self::REQUEST_URL_PATTERN, [
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
        $I->sendPOST(self::REQUEST_URL_PATTERN, [
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

    public function requestPaymentAndSelectWithInsufficientBalance(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::REQUEST_URL_PATTERN, [
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '105SAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $accountId = current($I->grabDataFromResponseByJsonPath('$.accountId'));
        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '105000MSAT',
            'state' => 'requested'
        ]]);

        $this->loginProfile($I, 'customer2-verified');
        $I->sendPOST(sprintf(self::SELECT_URL_PATTERN, $paymentId), ['service' => 'testtransfer']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['$' => ['Insufficient balance.']]]);
    }

    public function requestPaymentAndSelectForSameAccount(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::REQUEST_URL_PATTERN, [
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '102SAT',
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

        $I->sendPOST(sprintf(self::SELECT_URL_PATTERN, $paymentId), ['service' => 'testtransfer']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['$' => ['Cannot transfer from same account.']]]);
    }

    public function requestPaymentAndCancel(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::REQUEST_URL_PATTERN, [
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '104SAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $accountId = current($I->grabDataFromResponseByJsonPath('$.accountId'));
        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));

        $I->sendPOST(sprintf(self::CANCEL_URL_PATTERN, $paymentId));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'state' => 'cancelled'
        ]]);

        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '500000000MSAT']
        ]]);
    }

    public function requestPaymentAndSettle(ApiTester $I): void
    {
        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(self::REQUEST_URL_PATTERN, [
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '105SAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $accountId = current($I->grabDataFromResponseByJsonPath('$.accountId'));
        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_REQUESTED_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '105000MSAT',
            'state' => 'requested'
        ]]);

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(sprintf(self::SELECT_URL_PATTERN, $paymentId), ['service' => 'testtransfer']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => ['state' => 'selected']]);
        $newPaymentId = current($I->grabDataFromResponseByJsonPath('$._source.transaction.paymentId'));
        $payerAccountId = current($I->grabDataFromResponseByJsonPath('$._source.transaction.accountId'));

        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getAccount($payerAccountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '499895000MSAT']
        ]]);
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '105000MSAT',
            'amountPaid' => '105000MSAT',
            'state' => 'settled'
        ]]);
        $I->getPayment($newPaymentId);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '105000MSAT',
            'amountPaid' => '105000MSAT',
            'state' => 'completed'
        ]]);

        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '1000105000MSAT']
        ]]);
    }
}
