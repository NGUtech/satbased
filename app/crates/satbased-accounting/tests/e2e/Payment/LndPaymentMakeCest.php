<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\HttpCode;

class LndPaymentMakeCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/payments/make';
    private const URL_APPROVE_PATTERN = '/payments/%s/approve';
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
            'service' => 'testlnd',
            'description' => 'payment description',
            'amount' => '-100000MSAT',
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'amount' => ['Amount must be at least 1MSAT.']
        ]]);
    }

    public function makePaymentWithInsufficentBalance(ApiTester $I): void
    {
        $I->runStack('alice', 'addinvoice 4000000');
        $paymentRequest = current($I->grabDataFromOutputByJsonPath('$.payment_request'));

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testlnd',
            'description' => 'payment description',
            'amount' => '4000000SAT',
            'transaction' => ['request' => $paymentRequest]
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'amount' => ['Insufficient balance.']
        ]]);
    }

    public function makePaymentUnderMinimiumAmount(ApiTester $I): void
    {
        $I->runStack('bob', 'addinvoice 1');
        $paymentRequest = current($I->grabDataFromOutputByJsonPath('$.payment_request'));

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testlnd',
            'description' => 'payment description',
            'amount' => '1SAT',
            'transaction' => ['request' => $paymentRequest]
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'transaction' => ['Payment service cannot send given amount.']
        ]]);
    }

    public function makePaymentOverMaximumAmount(ApiTester $I): void
    {
        $I->runStack('bob', 'addinvoice 9000000');
        $paymentRequest = current($I->grabDataFromOutputByJsonPath('$.payment_request'));

        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testlnd',
            'description' => 'payment description',
            'amount' => '0.09BTC',
            'transaction' => ['request' => $paymentRequest]
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'transaction' => ['Payment service cannot send given amount.']
        ]]);
    }

    //@todo test with routing fees

    public function makePaymentAndRescan(ApiTester $I): void
    {
        $I->runStack('bob', 'addinvoice 1000');
        $paymentRequest = current($I->grabDataFromOutputByJsonPath('$.payment_request'));
        $I->runStack('bob', "decodepayreq $paymentRequest");
        $preimageHash = current($I->grabDataFromOutputByJsonPath('$.payment_hash'));

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testlnd',
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '1000sat',
            'transaction' => ['request' => $paymentRequest]
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_MADE_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'service' => 'testlnd',
            'amount' => '1000000MSAT',
            'transaction' => [
                'request' => $paymentRequest,
                'feeLimit' => 0.5,
                'feeEstimate' => '0MSAT',
                'label' => $paymentId
            ],
            'state' => 'made'
        ]]);

        $approvalToken = current($I->grabDataFromResponseByJsonPath('$._source.tokens.0.token'));
        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '499000000MSAT']
        ]]);

        $I->sendGET(sprintf(self::URL_APPROVE_PATTERN, $paymentId), ['t' => $approvalToken]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '1000000MSAT',
            'transaction' => [
                'request' => $paymentRequest,
                'preimageHash' => $preimageHash,
                'feeSettled' => '0MSAT'
            ],
            'state' => 'sent'
        ]]);

        $I->sendGET(sprintf(self::URL_RESCAN_PATTERN, $paymentId));
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->seeResponseEquals(null);

        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '1000000MSAT',
            'amountPaid' => '1000000MSAT',
            'state' => 'completed'
        ]]);

        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '499000000MSAT']
        ]]);
    }

    public function makePaymentAndComplete(ApiTester $I): void
    {
        $I->runStack('bob', 'addinvoice 100');
        $paymentRequest = current($I->grabDataFromOutputByJsonPath('$.payment_request'));
        $I->runStack('bob', "decodepayreq $paymentRequest");
        $preimageHash = current($I->grabDataFromOutputByJsonPath('$.payment_hash'));

        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testlnd',
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '100000MSAT',
            'transaction' => [
                'request' => $paymentRequest,
                'feeLimit' => 1.5
            ]
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_MADE_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'service' => 'testlnd',
            'amount' => '100000MSAT',
            'transaction' => [
                'request' => $paymentRequest,
                'feeLimit' => 1.5,
                'feeEstimate' => '0MSAT',
                'label' => $paymentId
            ],
            'state' => 'made'
        ]]);

        $approvalToken = current($I->grabDataFromResponseByJsonPath('$._source.tokens.0.token'));
        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '9999900000MSAT']
        ]]);

        $I->sendGET(sprintf(self::URL_APPROVE_PATTERN, $paymentId), ['t' => $approvalToken]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '100000MSAT',
            'transaction' => [
                'request' => $paymentRequest,
                'preimageHash' => $preimageHash,
                'feeSettled' => '0MSAT'
            ],
            'state' => 'sent'
        ]]);

        //@todo https://github.com/lightningnetwork/lnd/issues/4164
    }

    /**
     * @skip LND settlement/failure notifcation required for completion
     */
    public function makePaymentAndCompleteToHeldInvoice(ApiTester $I): void
    {
        $preimage = hash('sha256', 'hodltest'.rand(0, 100));
        $preimageHash = hash('sha256', hex2bin($preimage));

        $I->runStack('bob', "addholdinvoice $preimageHash 101");
        $I->seeInStackOutput('pay_req');
        $paymentRequest = current($I->grabDataFromOutputByJsonPath('$.pay_req'));

        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(self::URL_PATTERN, [
            'service' => 'testlnd',
            'references' => ['someid' => 'someref'],
            'description' => 'payment description',
            'amount' => '101SAT',
            'transaction' => [
                'request' => $paymentRequest,
                'feeLimit' => 2
            ]
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        $paymentId = current($I->grabDataFromResponseByJsonPath('$.paymentId'));
        $I->getPayment($paymentId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PAYMENT_MADE_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'service' => 'testlnd',
            'amount' => '101000MSAT',
            'transaction' => [
                'request' => $paymentRequest,
                'feeLimit' => 2.0,
                'feeEstimate' => '0MSAT'
            ],
            'state' => 'made'
        ]]);

        $approvalToken = current($I->grabDataFromResponseByJsonPath('$._source.tokens.0.token'));
        $accountId = current($I->grabDataFromResponseByJsonPath('$._source.accountId'));
        $I->getAccount($accountId);
        $I->seeResponseContainsJson(['_source' => [
            'wallet' => ['MSAT' => '4899000MSAT']
        ]]);

        $I->sendGET(sprintf(self::URL_APPROVE_PATTERN, $paymentId), ['t' => $approvalToken]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->runWorker('satbased.accounting.messages', 'daikon.message_queue');
        $I->getPayment($paymentId);
        $I->seeResponseContainsJson(['_source' => [
            'amount' => '101000MSAT',
            'transaction' => [
                'request' => $paymentRequest,
                'preimageHash' => $preimageHash,
                'feeSettled' => '0MSAT'
            ],
            'state' => 'sent'
        ]]);

        //@todo https://github.com/lightningnetwork/lnd/issues/4164
    }
}
