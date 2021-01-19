<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Codeception\Util\HttpCode;
use Satbased\Security\Entity\VerificationToken;

class ProfileVerifyCest
{
    use ApiCestTrait;

    private const URL_PATTERN = '/profiles/%s/verify';

    public function beforeAllTests(ApiTester $I): void
    {
        $I->setup();
    }

    public function getWithInvalidAcceptHeader(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/javascript');
        $I->sendGET(sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-pending']));
        $I->seeResponseCodeIs(HttpCode::NOT_ACCEPTABLE);
        $I->seeResponseEquals(null);
    }

    public function verifyWithoutToken(ApiTester $I): void
    {
        $I->sendGET(sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-pending']));
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => [
                't' => ['Missing required input.']
            ]
        ]);
    }

    public function verifyWithEmptyToken(ApiTester $I): void
    {
        $I->sendGET(sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-pending']), ['t' => '']);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => [
                't' => ['Invalid format.']
            ]
        ]);
    }

    public function verifyWithInvalidToken(ApiTester $I): void
    {
        $I->sendGET(sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-pending']), ['t' => 'abc']);
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'errors' => [
                't' => ['Invalid format.']
            ]
        ]);
    }

    public function verifyWithIncorrectToken(ApiTester $I): void
    {
        $I->sendGET(
            sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-pending']),
            ['t' => '0000000000000000000000000000000000000000000000000000000000000000']
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['$' => ['Token is not verified.']]]);
    }

    public function verifyVerifiedProfile(ApiTester $I): void
    {
        $I->sendGET(
            sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-verified']),
            ['t' => '0000000000000000000000000000000000000000000000000000000000000000']
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['$' => ['Profile cannot be verified.']]]);
    }

    public function verifyClosedProfile(ApiTester $I): void
    {
        $I->sendGET(
            sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-closed']),
            ['t' => '0000000000000000000000000000000000000000000000000000000000000000']
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['errors' => ['$' => ['Profile cannot be verified.']]]);
    }

    public function verifyPendingCustomer(ApiTester $I): void
    {
        $I->getProfile($this->PROFILE_IDS['customer-pending']);
        $token = current($I->grabDataFromResponseByJsonPath('$._source.tokens[1].token'));
        $I->sendGET(sprintf(self::URL_PATTERN, $this->PROFILE_IDS['customer-pending']), ['t' => $token]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->getProfile($this->PROFILE_IDS['customer-pending']);
        $I->seeResponseContainsJson(['_source' => ['state' => 'verified']]);
        $I->cantSeeResponseContainsJson(['_source' => [
            'tokens' => [
                ['@type' => VerificationToken::class]
            ]
        ]]);
    }
}
