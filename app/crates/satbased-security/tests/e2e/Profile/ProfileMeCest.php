<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Example;
use Codeception\Util\HttpCode;

class ProfileMeCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/profiles/me';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
    }

    public function getWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendGET(self::URL_PATTERN);
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function getWithoutCredentials(ApiTester $I): void
    {
        $I->sendGET(self::URL_PATTERN);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseEquals(null);
    }

    public function postWithoutCredentials(ApiTester $I): void
    {
        $I->sendPOST(self::URL_PATTERN);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseEquals(null);
    }

    public function getWithMissingJWT(ApiTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer blah');
        $I->sendGET(self::URL_PATTERN);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseEquals(null);
    }

    public function getWithMissingXsrfToken(ApiTester $I): void
    {
        $jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJUaWNrZXRTdHJlYW0iLCJhdWQiOiJUa'.
            'WNrZXRTdHJlYW0iLCJleHAiOjE1OTg2Mzc0NjgsIm5iZiI6MTU5NTk1OTA2OCwiaWF0IjoxNTk1OTU5MDY'.
            '4LCJqdGkiOiIyZGY2OWQwYS0xOTg1LTQwZDMtOGJmNS00MWQ2MmVmZTgwNDAiLCJ4c3JmIjoiMzNhZDEwNW'.
            'MwNGI4NmQ5MmI2ZTQ3NTk2NGFkYjgwYzA3ZTliZjA3NTA1ZGU4ZDU4MzE3YzRlMjgyODUzYTA4OCIsInVp'.
            'ZCI6InRpY2tldHN0cmVhbS5zZWN1cml0eS5wcm9maWxlLWY4M2FjMjA4LTU5YTctNDRlMi1iYWU3LTg1Nzl'.
            'hMWYwMGIxZiJ9.URdfUN3tSZeGtdVHnKERn3MJZV8omFW1usmFoSolTUM';
        $I->haveHttpHeader('Authorization', "Bearer $jwt");
        $I->sendGET(self::URL_PATTERN);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseEquals(null);
    }

    /**
     * @dataProvider authorizedRequestProvider
     */
    public function expectAuthorizedResponse(ApiTester $I, Example $request): void
    {
        list($role, $state) = explode('-', $request[0]);
        $this->loginProfile($I, $request[0]);
        $I->sendGET(self::URL_PATTERN);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            '@type' => 'Satbased\Security\ReadModel\Standard\Profile',
            'email' => "{$request[0]}@satbased.com",
            'name' => $role.' '.$state,
            'role' => rtrim($role, '0..9'),
            'state' => $state
        ]);
        $I->dontSeeResponseContainsJsonKey('passwordHash');
        $I->seeResponseMatchesJsonType($this->PROFILE_TYPE);
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
