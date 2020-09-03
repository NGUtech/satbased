<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Account;

use Daikon\Boot\Validator\DaikonRequestValidator;
use Daikon\Config\ConfigProviderInterface;
use Daikon\Security\Authorization\AuthorizationServiceInterface;
use Psr\Log\LoggerInterface;
use Satbased\Api\SecureResourceAction;

abstract class AccountAction extends SecureResourceAction
{
    protected LoggerInterface $logger;

    protected ConfigProviderInterface $config;

    protected AuthorizationServiceInterface $authorizationService;

    protected DaikonRequestValidator $requestValidator;

    public function __construct(
        LoggerInterface $logger,
        ConfigProviderInterface $config,
        AuthorizationServiceInterface $authorizationService,
        DaikonRequestValidator $requestValidator
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->authorizationService = $authorizationService;
        $this->requestValidator = $requestValidator;
    }
}
