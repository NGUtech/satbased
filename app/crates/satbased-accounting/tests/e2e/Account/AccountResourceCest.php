<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Example;
use Codeception\Util\HttpCode;
use Satbased\Accounting\ValueObject\AccountId;

class AccountResourceCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/accounts/%s';

    private array $ACCOUNT_IDS = [];

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
        $I->searchAccounts();
        foreach ($I->grabDataFromResponseByJsonPath('$.hits.hits[*]') as $doc) {
            $profile = array_search($doc['_source']['profileId'], $this->PROFILE_IDS);
            $this->ACCOUNT_IDS[$profile] = $doc['_id'];
        }
    }

    public function getWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendGET(sprintf(self::URL_PATTERN, current($this->ACCOUNT_IDS)));
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function getWithoutCredentials(ApiTester $I): void
    {
        $I->sendGET(sprintf(self::URL_PATTERN, current($this->ACCOUNT_IDS)));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseEquals(null);
    }

    public function postWithoutCredentials(ApiTester $I): void
    {
        $I->sendPOST(sprintf(self::URL_PATTERN, current($this->ACCOUNT_IDS)));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseEquals(null);
    }

    public function getUnmatchedRouteAccount(ApiTester $I): void
    {
        $this->loginProfile($I, 'admin-verified');
        $I->sendGET(sprintf(self::URL_PATTERN, 'invalid-id'));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseEquals(null);
    }

    public function getNotFoundAccount(ApiTester $I): void
    {
        $this->loginProfile($I, 'admin-verified');
        $I->sendGET(sprintf(self::URL_PATTERN, AccountId::PREFIX.'-00000000-0000-0000-0000-000000000000'));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => ['accountId' => ['Not found.']]
        ]);
    }

    private function getAccountResource(ApiTester $I, string $resource, string $profile): void
    {
        $this->loginProfile($I, $profile);
        $I->sendGET(sprintf(self::URL_PATTERN, $this->ACCOUNT_IDS[$resource]));
        $I->seeHttpHeader('Content-Type', 'application/json');
    }

    /**
     * @dataProvider authorizedRequestProvider
     */
    public function expectAuthorizedResponse(ApiTester $I, Example $request): void
    {
        $this->getAccountResource($I, $request[0], $request[1]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType($this->ACCOUNT_TYPE);
    }

    /**
     * @dataProvider forbiddenRequestProvider
     */
    public function expectForbiddenResponse(ApiTester $I, Example $request): void
    {
        $this->getAccountResource($I, $request[0], $request[1]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeResponseEquals(null);
    }

    private function authorizedRequestProvider(): array
    {
        return [
            ['admin-verified', 'admin-verified'],
            ['admin-closed', 'admin-verified'],
            ['staff-verified', 'admin-verified'],
            ['staff-closed', 'admin-verified'],
            ['customer-pending', 'admin-verified'],
            ['customer-verified', 'admin-verified'],
            ['customer2-verified', 'admin-verified'],
            ['customer-closed', 'admin-verified'],
            ['admin-verified', 'staff-verified'],
            ['admin-closed', 'staff-verified'],
            ['staff-verified', 'staff-verified'],
            ['staff-closed', 'staff-verified'],
            ['customer-verified', 'staff-verified'],
            ['customer2-verified', 'staff-verified'],
            ['customer-closed', 'staff-verified'],
            ['customer-pending', 'staff-verified'],
            ['customer-verified', 'customer-verified'],
            ['customer2-verified', 'customer2-verified'],
            ['customer-pending', 'customer-pending'],
        ];
    }

    private function forbiddenRequestProvider(): array
    {
        return [
            ['admin-verified', 'customer-verified'],
            ['admin-closed', 'customer-verified'],
            ['staff-verified', 'customer-verified'],
            ['staff-closed', 'customer-verified'],
            ['customer2-verified', 'customer-verified'],
            ['customer-pending', 'customer-verified'],
            ['customer-closed', 'customer-verified'],
            ['admin-verified', 'customer2-verified'],
            ['admin-closed', 'customer2-verified'],
            ['staff-verified', 'customer2-verified'],
            ['staff-closed', 'customer2-verified'],
            ['customer-verified', 'customer2-verified'],
            ['customer-pending', 'customer2-verified'],
            ['customer-closed', 'customer2-verified'],
            ['admin-verified', 'customer-pending'],
            ['admin-closed', 'customer-pending'],
            ['staff-verified', 'customer-pending'],
            ['staff-closed', 'customer-pending'],
            ['customer-verified', 'customer-pending'],
            ['customer2-verified', 'customer-pending'],
            ['customer-closed', 'customer-pending'],
        ];
    }
}
