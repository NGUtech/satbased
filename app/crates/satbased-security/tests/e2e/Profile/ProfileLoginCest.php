<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Example;
use Codeception\Util\HttpCode;
use Satbased\Security\Entity\AuthenticationToken;

class ProfileLoginCest
{
    use ApiCestTrait;

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
    }

    public function postWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendPOST('/login');
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function postWithoutCredentials(ApiTester $I): void
    {
        $I->sendPOST('/login');
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => [
                'email' => ['Missing required input.'],
                'password' => ['Missing required input.']
            ]
        ]);
    }

    public function getWithoutCredentials(ApiTester $I): void
    {
        $I->sendGET('/login');
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseEquals(null);
    }

    //@todo test with a jwt payload

    /**
     * @dataProvider authorizedRequestProvider
     */
    public function expectAuthorizedResponse(ApiTester $I, Example $request): void
    {
        $this->loginProfile($I, $request[0]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeValidAuthenticationCookies();
        $expiresAt = current($I->grabDataFromResponseByJsonPath('$.authenticationTokenExpiresAt'));
        $I->getProfile($this->PROFILE_IDS[$request[0]]);
        $I->seeResponseContainsJson(['_source' => [
            'tokens' => [
                [
                    '@type' => AuthenticationToken::class,
                    'expiresAt' => $expiresAt
                ]
            ]
        ]]);
    }

    /**
     * @dataProvider forbiddenRequestProvider
     */
    public function expectForbiddenResponse(ApiTester $I, Example $request): void
    {
        $I->sendPOST('/login', ['email' => "{$request[0]}@satbased.com", 'password' => 'password']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['$' => ['Profile cannot be logged in.']]]);
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

    private function forbiddenRequestProvider(): array
    {
        return [
            ['admin-closed'],
            ['staff-closed'],
            ['customer-closed'],
        ];
    }
}
