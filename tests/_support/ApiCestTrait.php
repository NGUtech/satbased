<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Ramsey\Uuid\Uuid;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\PaymentId;
use Satbased\Security\ValueObject\ProfileId;

trait ApiCestTrait
{
    private array $PROFILE_IDS = [
        'admin-verified' => ProfileId::PREFIX.'-faadc643-31ed-49ba-8c41-bf1dedab4f37',
        'admin-closed' => ProfileId::PREFIX.'-8cf3b691-f02b-4467-9e89-3b148071fecb',
        'staff-verified' => ProfileId::PREFIX.'-f1638d17-98b7-477c-ade8-b277b8799433',
        'staff-closed' => ProfileId::PREFIX.'-9637c2e9-2401-48d6-b696-47606b97168d',
        'customer-pending' => ProfileId::PREFIX.'-4ba173e0-4795-4730-a287-74f5bafca632',
        'customer-verified' => ProfileId::PREFIX.'-f83ac208-59a7-44e2-bae7-8579a1f00b1f',
        'customer2-verified' => ProfileId::PREFIX.'-60e15790-af38-4ec9-b43b-ef6dd54bfee1',
        'customer-closed' => ProfileId::PREFIX.'-6280b20e-6e6d-4d45-9b4b-77a2981613d9',
    ];

    private array $PAYMENT_IDS = [
        'customer-verified' => PaymentId::PREFIX.'-13c706c7-fb86-46a5-aa4b-ed3b60a6e988',
        'staff-verified' => PaymentId::PREFIX.'-d4a00738-cc52-4be2-9f7a-640232477772'
    ];

    private array $PROFILE_TYPE = [
        '@type' => 'string:!empty',
        'profileId' => 'string:regex('.ProfileId::PATTERN.')',
        'revision' => 'integer:>0',
        'name' => 'string:!empty',
        'email' => 'string:email',
        'language' => 'string:regex(/(en|ar)/)',
        'role' => 'string:!empty',
        'state' => 'string:!empty',
        'registeredAt' => 'string:date',
        'tokens' => [[
            '@type' => 'string:!empty',
            'id' => 'string:regex(/'.Uuid::VALID_PATTERN.'/)',
            'expiresAt' => 'string:date',
            'token' => 'string:regex(/^[a-f0-9]{64}$/)'
        ]],
    ];

    private array $ACCOUNT_TYPE = [
        '@type' => 'string:!empty',
        'accountId' => 'string:regex('.AccountId::PATTERN.')',
        'revision' => 'integer:>0',
        'profileId' => 'string:regex('.ProfileId::PATTERN.')',
        // 'wallet' => 'array',
        'state' => 'string:!empty',
        'openedAt' => 'string:date',
    ];

    private array $PAYMENT_REQUESTED_TYPE = [
        '@type' => 'string:!empty',
        'paymentId' => 'string:regex('.PaymentId::PATTERN.')',
        'revision' => 'integer:>0',
        'profileId' => 'string:regex('.ProfileId::PATTERN.')',
        'accountId' => 'string:regex('.AccountId::PATTERN.')',
        'references' => 'array',
        'accepts' => 'array',
        'amount' => 'string:regex(/^[0-9]+MSAT$/)',
        'description' => 'string',
        // 'service' => 'string:!empty',
        // 'transaction' => 'array',
        'requestedAt' => 'string:date',
        'state' => 'string:!empty',
        'direction' => 'string:regex(/incoming/)',
        // 'receivedAt' => 'null|string:date',
        // 'selectedAt' => 'null|string:date',
        // 'settledAt' => 'null|string:date',
        // 'cancelledAt' => 'null|string:date',
    ];

    private array $PAYMENT_MADE_TYPE = [
        '@type' => 'string:!empty',
        'paymentId' => 'string:regex('.PaymentId::PATTERN.')',
        'revision' => 'integer:>0',
        'profileId' => 'string:regex('.ProfileId::PATTERN.')',
        'accountId' => 'string:regex('.AccountId::PATTERN.')',
        'references' => 'array',
        'amount' => 'string:regex(/^[0-9]+MSAT$/)',
        'description' => 'string',
        'service' => 'string:!empty',
        'transaction' => 'array',
        'requestedAt' => 'string:date',
        'state' => 'string:!empty',
        'direction' => 'string:regex(/outgoing/)',
        'tokens' => 'array',
        // 'cancelledAt' => 'null|string:date',
        // 'completedAt' => 'null|string:date',
        // 'approvedAt' => 'null|string:date',
        // 'sentAt' => 'null|string:date',
        // 'failedAt' => 'null|string:date',
    ];

    private function loginProfile(ApiTester $I, string $account): void
    {
        if ($account !== 'unauthenticated') {
            $auth = $I->login(['email' => "$account@satbased.com", 'password' => 'password']);
            $I->amBearerAuthenticated($auth['jwt']);
            $I->haveHttpHeader('X-XSRF-TOKEN', $auth['xsrf']);
            $I->canSeeHttpHeader('Content-Type', 'application/json');
            $I->seeResponseIsJson();
        }
    }
}
