<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile;

use Daikon\Boot\Validator\DaikonRequestValidator;
use Daikon\Config\ConfigProviderInterface;
use Daikon\Security\Authorization\AuthorizationServiceInterface;
use Psr\Log\LoggerInterface;
use Satbased\Api\SecureResourceAction;
use Satbased\Security\ReadModel\Standard\ProfileCollection;

abstract class ProfileAction extends SecureResourceAction
{
    protected LoggerInterface $logger;

    protected ConfigProviderInterface $config;

    protected AuthorizationServiceInterface $authorizationService;

    protected DaikonRequestValidator $requestValidator;

    protected ProfileCollection $profileCollection;

    protected ProfileService $profileService;

    public function __construct(
        LoggerInterface $logger,
        ConfigProviderInterface $config,
        AuthorizationServiceInterface $authorizationService,
        DaikonRequestValidator $requestValidator,
        ProfileCollection $profileCollection,
        ProfileService $profileService
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->authorizationService = $authorizationService;
        $this->requestValidator = $requestValidator;
        $this->profileCollection = $profileCollection;
        $this->profileService = $profileService;
    }
}
