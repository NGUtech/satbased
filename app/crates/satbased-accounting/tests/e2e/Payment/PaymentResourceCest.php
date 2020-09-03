<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Example;
use Codeception\Util\HttpCode;
use Satbased\Accounting\ValueObject\PaymentId;

class PaymentResourceCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/payments/%s';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
    }

    public function getWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendGET(sprintf(self::URL_PATTERN, current($this->PAYMENT_IDS)));
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function getWithoutCredentials(ApiTester $I): void
    {
        $I->sendGET(sprintf(self::URL_PATTERN, current($this->PAYMENT_IDS)));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseEquals(null);
    }

    public function postWithoutCredentials(ApiTester $I): void
    {
        $I->sendPOST(sprintf(self::URL_PATTERN, current($this->PAYMENT_IDS)));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseEquals(null);
    }

    public function getUnmatchedRoutePayment(ApiTester $I): void
    {
        $this->loginProfile($I, 'admin-verified');
        $I->sendGET(sprintf(self::URL_PATTERN, 'invalid-id'));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseEquals(null);
    }

    public function getNotFoundPayment(ApiTester $I): void
    {
        $this->loginProfile($I, 'admin-verified');
        $I->sendGET(sprintf(self::URL_PATTERN, PaymentId::PREFIX.'-00000000-0000-0000-0000-000000000000'));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['paymentId' => ['Not found.']]]);
    }

    private function getPaymentResource(ApiTester $I, string $resource, string $profile): void
    {
        $this->loginProfile($I, $profile);
        $I->sendGET(sprintf(self::URL_PATTERN, $this->PAYMENT_IDS[$resource]));
        $I->seeHttpHeader('Content-Type', 'application/json');
    }

    /**
     * @dataProvider authorizedRequestProvider
     */
    public function expectAuthorizedResponse(ApiTester $I, Example $request): void
    {
        $this->getPaymentResource($I, $request[0], $request[1]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType($this->PAYMENT_REQUESTED_TYPE);
    }

    /**
     * @dataProvider forbiddenRequestProvider
     */
    public function expectForbiddenResponse(ApiTester $I, Example $request): void
    {
        $this->getPaymentResource($I, $request[0], $request[1]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeResponseEquals(null);
    }

    private function authorizedRequestProvider(): array
    {
        return [
            ['customer-verified', 'admin-verified'],
            ['customer-verified', 'staff-verified'],
            ['customer-verified', 'customer-verified'],
        ];
    }

    private function forbiddenRequestProvider(): array
    {
        return [
            ['customer-verified', 'customer2-verified'],
            ['customer-verified', 'customer-pending'],
        ];
    }
}
