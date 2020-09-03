<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Login;

use Daikon\Boot\Middleware\Action\Action;
use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Validator\DaikonRequestValidator;
use Daikon\Validize\Validator\EmailValidator;
use Daikon\Validize\Validator\PasswordValidator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Api\ErrorResponder;
use Satbased\Security\Api\Profile\ProfileService;
use Throwable;

final class LoginAction extends Action
{
    private DaikonRequestValidator $requestValidator;

    private ProfileService $profileService;

    public function __construct(
        DaikonRequestValidator $requestValidator,
        ProfileService $profileService
    ) {
        $this->requestValidator = $requestValidator;
        $this->profileService = $profileService;
    }

    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        try {
            $profile = $this->profileService->authenticate(
                (string)$payload['email'],
                (string)$payload['password']
            );
            $loginProfile = $this->profileService->login($profile);
            $jwt = $this->profileService->generateJWT($profile);
        } catch (Throwable $error) {
            sleep(2);
            throw $error;
        }

        return $request->withResponder(
            [LoginResponder::class, [':loginProfile' => $loginProfile, ':jwt' => $jwt]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->error('email', EmailValidator::class)
            ->error('password', PasswordValidator::class);
    }

    public function handleError(DaikonRequest $request): DaikonRequest
    {
        return $request->withResponder(ErrorResponder::class);
    }
}
