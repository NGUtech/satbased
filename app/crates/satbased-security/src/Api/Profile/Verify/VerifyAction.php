<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Verify;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Validize\Validator\Sha256Validator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Security\Api\Profile\ProfileValidator;
use Satbased\Security\Api\Profile\ProfileAction;
use Throwable;

final class VerifyAction extends ProfileAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        try {
            //@todo unify verification and login
            $verifyProfile = $this->profileService->verify($payload['profile'], $payload['token']);
        } catch (Throwable $error) {
            sleep(2);
            throw $error;
        }

        return $request->withResponder(
            [VerifyResponder::class, [':verifyProfile' => $verifyProfile]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->critical('t', Sha256Validator::class, ['export' => 'token'])
            ->critical('profileId', ProfileValidator::class, [
                'export' => 'profile',
                'status' => self::STATUS_NOT_FOUND
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        return true;
    }

    public function isSecure(): bool
    {
        return false;
    }
}
