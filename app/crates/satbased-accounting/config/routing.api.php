<?php declare(strict_types=1);

use Satbased\Accounting\Api\Account\My\MyAction;
use Satbased\Accounting\Api\Account\Resource\ResourceAction as AccountResourceAction;
use Satbased\Accounting\Api\Payment\Cancel\CancelAction;
use Satbased\Accounting\Api\Payment\Make\MakeAction;
use Satbased\Accounting\Api\Payment\Request\RequestAction;
use Satbased\Accounting\Api\Payment\Rescan\RescanAction;
use Satbased\Accounting\Api\Payment\Resource\ResourceAction as PaymentResourceAction;
use Satbased\Accounting\Api\Payment\Search\SearchAction;
use Satbased\Accounting\Api\Payment\Select\SelectAction;
use Satbased\Accounting\Api\Payment\Services\ServicesAction;
use Satbased\Accounting\Api\Payment\Settle\SettleAction;
use Satbased\Accounting\ValueObject\AccountId;
use Satbased\Accounting\ValueObject\PaymentId;

$cratePrefix = 'satbased.accounting';
$uuidPattern = '[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}';
$mount = $configProvider->get("crates.$cratePrefix.mount", '/satbased/accounting');

$map->attach("$cratePrefix.", $mount, function ($map) use ($uuidPattern) {
    $map->get('account.resource', '/accounts/{accountId}', AccountResourceAction::class)
        ->tokens(['accountId' => AccountId::PREFIX."-$uuidPattern"]);

    $map->get('account.my', '/accounts/my', MyAction::class);

    $map->get('payment.search', '/payments', SearchAction::class);

    $map->get('payment.resource', '/payments/{paymentId}', PaymentResourceAction::class)
        ->tokens(['paymentId' => PaymentId::PREFIX."-$uuidPattern"]);

    $map->post('payment.request', '/payments/request', RequestAction::class);

    $map->post('payment.make', '/payments/make', MakeAction::class);

    $map->post('payment.select', '/payments/{paymentId}/select', SelectAction::class)
        ->tokens(['paymentId' => PaymentId::PREFIX."-$uuidPattern"]);

    $map->post('payment.settle', '/payments/{paymentId}/settle', SettleAction::class)
        ->tokens(['paymentId' => PaymentId::PREFIX."-$uuidPattern"]);

    $map->post('payment.cancel', '/payments/{paymentId}/cancel', CancelAction::class)
        ->tokens(['paymentId' => PaymentId::PREFIX."-$uuidPattern"]);

    $map->get('payment.services', '/payments/{paymentId}/services', ServicesAction::class)
        ->tokens(['paymentId' => PaymentId::PREFIX."-$uuidPattern"]);

    $map->get('payment.rescan', '/payments/{paymentId}/rescan', RescanAction::class)
        ->tokens(['paymentId' => PaymentId::PREFIX."-$uuidPattern"]);
});
