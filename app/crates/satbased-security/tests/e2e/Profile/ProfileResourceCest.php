<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Example;
use Codeception\Util\HttpCode;
use Satbased\Security\ValueObject\ProfileId;

class ProfileResourceCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/profiles/%s';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
    }

    public function getWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendGET(sprintf(self::URL_PATTERN, current($this->PROFILE_IDS)));
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function getWithoutCredentials(ApiTester $I): void
    {
        $I->sendGET(sprintf(self::URL_PATTERN, current($this->PROFILE_IDS)));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseEquals(null);
    }

    public function postWithoutCredentials(ApiTester $I): void
    {
        $I->sendPOST(sprintf(self::URL_PATTERN, current($this->PROFILE_IDS)));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseEquals(null);
    }

    public function getUnmatchedRouteProfile(ApiTester $I): void
    {
        $this->loginProfile($I, 'admin-verified');
        $I->sendGET(sprintf(self::URL_PATTERN, 'invalid-id'));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseEquals(null);
    }

    public function getNotFoundProfile(ApiTester $I): void
    {
        $this->loginProfile($I, 'admin-verified');
        $I->sendGET(sprintf(self::URL_PATTERN, ProfileId::PREFIX.'-00000000-0000-0000-0000-000000000000'));
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => ['profileId' => ['Not found.']]
        ]);
    }

    private function getProfileResource(ApiTester $I, string $resource, string $profile): void
    {
        $this->loginProfile($I, $profile);
        $I->sendGET(sprintf(self::URL_PATTERN, $this->PROFILE_IDS[$resource]));
        $I->seeHttpHeader('Content-Type', 'application/json');
    }

    /**
     * @dataProvider authorizedRequestProvider
     */
    public function expectAuthorizedResponse(ApiTester $I, Example $request): void
    {
        list($role, $state) = explode('-', $request[0]);
        $this->getProfileResource($I, $request[0], $request[1]);
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

    /**
     * @dataProvider forbiddenRequestProvider
     */
    public function expectForbiddenResponse(ApiTester $I, Example $request): void
    {
        $this->getProfileResource($I, $request[0], $request[1]);
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
