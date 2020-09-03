<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Register;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Validize\Validator\ChoiceValidator;
use Daikon\Validize\Validator\EmailValidator;
use Daikon\Validize\Validator\PasswordValidator;
use Daikon\Validize\Validator\TextValidator;
use Daikon\Validize\Validator\ValidatorInterface;
use Daikon\ValueObject\Text;
use Satbased\Security\Api\Profile\EmailNotRegisteredValidator;
use Satbased\Security\Api\Profile\ProfileAction;

final class RegisterAction extends ProfileAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        $registerProfile = $this->profileService->register($payload);

        return $request->withResponder(
            [RegisterResponder::class, [':registerProfile' => $registerProfile]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        $lanugages = $this->config->get('project.negotiation.languages', ['en']);
        return $this->requestValidator
            ->error('name', TextValidator::class, ['min' => 4, 'max' => 64])
            ->error('password', PasswordValidator::class)
            ->error('language', ChoiceValidator::class, [
                'required' => false,
                'choices' => $lanugages,
                'default' => Text::fromNative($lanugages[0])
            ])
            ->critical('email', EmailValidator::class, ['provides' => 'valid_email'])
            ->critical('email', EmailNotRegisteredValidator::class, [
                'depends' => 'valid_email',
                'export' => false,
                'status' => self::STATUS_CONFLICT
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
