<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment;

use Daikon\Boot\Validator\DaikonRequestValidator;
use Daikon\Money\Service\PaymentServiceMap;
use Daikon\Security\Authorization\AuthorizationServiceInterface;
use Psr\Log\LoggerInterface;
use Satbased\Accounting\ReadModel\Standard\PaymentCollection;
use Satbased\Api\SecureResourceAction;

abstract class PaymentAction extends SecureResourceAction
{
    protected LoggerInterface $logger;

    protected AuthorizationServiceInterface $authorizationService;

    protected DaikonRequestValidator $requestValidator;

    protected PaymentServiceMap $paymentServiceMap;

    protected PaymentService $paymentService;

    protected PaymentCollection $paymentCollection;

    public function __construct(
        LoggerInterface $logger,
        AuthorizationServiceInterface $authorizationService,
        DaikonRequestValidator $requestValidator,
        PaymentServiceMap $paymentServiceMap,
        PaymentService $paymentService,
        PaymentCollection $paymentCollection
    ) {
        $this->logger = $logger;
        $this->authorizationService = $authorizationService;
        $this->requestValidator = $requestValidator;
        $this->paymentServiceMap = $paymentServiceMap;
        $this->paymentService = $paymentService;
        $this->paymentCollection = $paymentCollection;
    }
}
