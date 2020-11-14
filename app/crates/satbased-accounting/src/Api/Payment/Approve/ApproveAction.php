<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Approve;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\Sha256Validator;
use Daikon\Validize\Validator\ValidatorInterface;
use Satbased\Accounting\Api\Payment\PaymentAction;
use Satbased\Accounting\Api\Payment\PaymentValidator;
use Throwable;

final class ApproveAction extends PaymentAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $payload = $request->getPayload();

        try {
            $approvePayment = $this->paymentService->approve($payload['payment'], $payload['token']);
        } catch (Throwable $error) {
            sleep(2);
            throw $error;
        }

        return $request->withResponder(
            [ApproveResponder::class, [':approvePayment' => $approvePayment]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        //@todo approve with token
        return $this->requestValidator
            ->critical('t', Sha256Validator::class, ['export' => 'token'])
            ->critical('paymentId', PaymentValidator::class, [
                'export' => 'payment',
                'status' => self::STATUS_NOT_FOUND
            ]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        $resource = $request->getPayload()['payment'] ?? $this;
        return $this->authorizationService->isAllowed($role, $resource, 'payment.approve');
    }
}
