<?php declare(strict_types=1);

use Satbased\Security\Api\Profile\Close\CloseAction;
use Satbased\Security\Api\Profile\Register\RegisterAction;
use Satbased\Security\Api\Profile\Resource\ResourceAction;
use Satbased\Security\Api\Profile\Login\LoginAction;
use Satbased\Security\Api\Profile\Logout\LogoutAction;
use Satbased\Security\Api\Profile\Me\MeAction;
use Satbased\Security\Api\Profile\Promote\PromoteAction;
use Satbased\Security\Api\Profile\Verify\VerifyAction;
use Satbased\Security\ValueObject\ProfileId;

$cratePrefix = 'satbased.security';
$uuidPattern = '[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}';
$mount = $configProvider->get("crates.$cratePrefix.mount", '/satbased/security');

$map->attach("$cratePrefix.", $mount, function ($map) use ($uuidPattern) {
    $map->post('profile.login', '/login', LoginAction::class);

    $map->post('profile.logout', '/logout', LogoutAction::class);

    $map->post('profile.register', '/profiles', RegisterAction::class);

    $map->get('profile.me', '/profiles/me', MeAction::class);

    $map->get('profile.resource', '/profiles/{profileId}', ResourceAction::class)
        ->tokens(['profileId' => ProfileId::PREFIX."-$uuidPattern"]);

    $map->get('profile.verify', '/profiles/{profileId}/verify', VerifyAction::class)
        ->tokens(['profileId' => ProfileId::PREFIX."-$uuidPattern"]);

    $map->post('profile.close', '/profiles/{profileId}/close', CloseAction::class)
        ->tokens(['profileId' => ProfileId::PREFIX."-$uuidPattern"]);

    $map->post('profile.promote', '/profiles/{profileId}/promote', PromoteAction::class)
        ->tokens(['profileId' => ProfileId::PREFIX."-$uuidPattern"]);
});
