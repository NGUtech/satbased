<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\HttpCode;
use Satbased\Security\Entity\AuthenticationToken;
use Satbased\Security\Entity\VerificationToken;

class ProfileRegisterCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/profiles';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
    }

    public function postWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendPOST(self::URL_PATTERN);
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function getWithoutCredentials(ApiTester $I): void
    {
        $I->sendGET(self::URL_PATTERN);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseEquals(null);
    }

    public function registerProfileWithoutParams(ApiTester $I): void
    {
        $I->sendPOST(self::URL_PATTERN);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => [
                'name' => ['Missing required input.'],
                'email' => ['Missing required input.'],
                'password' => ['Missing required input.'],
                'language' => ['Missing required input.']
            ]
        ]);
    }

    public function registerProfileWithInvalidParams(ApiTester $I): void
    {
        $I->sendPOST(self::URL_PATTERN, ['name' => 'abc', 'email' => 'abc', 'password' => 'abc', 'language' => 'es']);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => [
                'name' => ['Must be between 4 and 64 characters.'],
                'email' => ['Invalid format.'],
                'password' => ['Must be between 8 and 60 characters.'],
                'language' => ['Not a valid choice.']
            ]
        ]);
    }

    public function registerProfileWithDuplicateEmail(ApiTester $I): void
    {
        $I->sendPOST(self::URL_PATTERN, [
            'name' => 'Test',
            'email' => 'admin-verified@satbased.com',
            'password' => 'password',
            'language' => 'en'
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => ['email' => ['Email already registered.']]
        ]);
    }

    public function registerProfileWithValidParams(ApiTester $I): void
    {
        $I->sendPOST(self::URL_PATTERN, [
            'name' => 'Test',
            'email' => 'test@satbased.com',
            'password' => 'password',
            'language' => 'en'
        ]);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
        $profileId = current($I->grabDataFromResponseByJsonPath('$.profileId'));
        $verificationTokenExpiresAt = current($I->grabDataFromResponseByJsonPath('$.verificationTokenExpiresAt'));
        $I->getProfile($profileId);
        $I->seeResponseMatchesJsonType(['_source' => $this->PROFILE_TYPE]);
        $I->seeResponseContainsJson(['_source' => [
            'state' => 'pending',
            'tokens' => [
                [
                    '@type' => AuthenticationToken::class,
                    'expiresAt' => '1970-01-01T00:00:00.000000+00:00'
                ],
                [
                    '@type' => VerificationToken::class,
                    'expiresAt' => $verificationTokenExpiresAt
                ]
            ]
        ]]);
    }
}
