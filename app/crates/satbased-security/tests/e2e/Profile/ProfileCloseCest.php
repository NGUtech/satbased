<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Example;
use Codeception\Util\HttpCode;
use Satbased\Security\Entity\AuthenticationToken;

class ProfileCloseCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/profiles/%s/close';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
    }

    public function postWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendPOST(sprintf(self::URL_PATTERN, current($this->PROFILE_IDS)));
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function postWithoutCredentials(ApiTester $I): void
    {
        $I->sendPOST(sprintf(self::URL_PATTERN, current($this->PROFILE_IDS)));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseEquals(null);
    }

    public function getWithoutCredentials(ApiTester $I): void
    {
        $I->sendGET(sprintf(self::URL_PATTERN, current($this->PROFILE_IDS)));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseEquals(null);
    }

    private function postProfileClose(ApiTester $I, string $profile): void
    {
        $this->loginProfile($I, $profile);
        $I->sendPOST(sprintf(self::URL_PATTERN, $this->PROFILE_IDS[$profile]));
        $I->seeHttpHeader('Content-Type', 'application/json');
    }

    /**
     * @dataProvider authorizedRequestProvider
     */
    public function expectAuthorizedResponse(ApiTester $I, Example $request): void
    {
        $this->postProfileClose($I, $request[0]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeExpiredAuthenticationCookies();
        $I->getProfile($this->PROFILE_IDS[$request[0]]);
        $I->seeResponseContainsJson(['_source' => [
            'state' => 'closed',
            'tokens' => [
                [
                    '@type' => AuthenticationToken::class,
                    'expiresAt' => '1970-01-01T00:00:00.000000+00:00'
                ]
            ]
        ]]);
    }

    //@todo unauthorized tests

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
