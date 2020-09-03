<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\HttpCode;

class ProfilePromoteCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/profiles/%s/promote';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
    }

    public function postWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendPOST(sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-verified']));
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function postWithoutCredentials(ApiTester $I): void
    {
        $I->sendPOST(sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-verified']));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseEquals(null);
    }

    public function getWithoutCredentials(ApiTester $I): void
    {
        $I->sendGET(sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-verified']));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseEquals(null);
    }

    public function promoteFromInvalidState(ApiTester $I): void
    {
        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(
            sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-pending']),
            ['role' => 'staff']
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['$' => ['Profile cannot be promoted.']]]);
    }

    public function promoteWithInvalidRole(ApiTester $I): void
    {
        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(
            sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-verified']),
            ['role' => 'invalid']
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => [
            'role' => ['Invalid role.']
        ]]);
    }

    public function promoteFromForbiddenRole(ApiTester $I): void
    {
        $this->loginProfile($I, 'customer-verified');
        $I->sendPOST(
            sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-verified']),
            ['role' => 's']
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeResponseEquals(null);
    }

    public function promotecustomerToAdmin(ApiTester $I): void
    {
        $this->loginProfile($I, 'staff-verified');
        $I->sendPOST(
            sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-verified']),
            ['role' => 'admin']
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getProfile($this->PROFILE_IDS['customer-verified']);
        $I->seeResponseContainsJson(['_source' => ['role' => 'admin']]);
    }
}
