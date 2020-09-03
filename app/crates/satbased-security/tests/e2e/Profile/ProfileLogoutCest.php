<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Example;
use Codeception\Util\HttpCode;
use Satbased\Security\Entity\AuthenticationToken;

class ProfileLogoutCest
{
    use ApiCestTrait;

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
    }

    public function postWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendPOST('/logout');
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function postWithoutCredentials(ApiTester $I): void
    {
        $I->sendPOST('/logout');
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->seeResponseEquals(null);
    }

    public function getWithoutCredentials(ApiTester $I): void
    {
        $I->sendGET('/logout');
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseEquals(null);
    }

    /**
     * @dataProvider authorizedRequestProvider
     */
    public function expectAuthorizedResponse(ApiTester $I, Example $request): void
    {
        $this->loginProfile($I, $request[0]);
        $I->sendPOST('/logout');
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->seeResponseEquals(null);
        $I->seeExpiredAuthenticationCookies();
        $I->getProfile($this->PROFILE_IDS[$request[0]]);
        $I->seeResponseContainsJson(['_source' => [
            'tokens' => [
                [
                    '@type' => AuthenticationToken::class,
                    'expiresAt' => '1970-01-01T00:00:00.000000+00:00'
                ]
            ]
        ]]);
    }

    private function authorizedRequestProvider(): array
    {
        return [
            ['admin-verified'],
            ['staff-verified'],
            ['customer-verified'],
            ['customer2-verified'],
            ['customer-pending'],
        ];
    }
}
