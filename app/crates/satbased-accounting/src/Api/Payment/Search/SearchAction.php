<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Search;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Security\Middleware\JwtAuthenticator;
use Daikon\Validize\Validator\RangeValidator;
use Daikon\Validize\Validator\ValidatorInterface;
use Daikon\ValueObject\Range;
use Satbased\Accounting\Api\Payment\PaymentAction;

final class SearchAction extends PaymentAction
{
    public function __invoke(DaikonRequest $request): DaikonRequest
    {
        $profile = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        $payload = $request->getPayload();

        $query = SearchQuery::build()
            ->withProfileId((string)$profile->getProfileId())
            ->withSort('requestedAt', 'desc');

        $result = $this->paymentCollection->search(
            $query,
            $payload['range']->getStart(),
            $payload['range']->getSize()
        );

        if ($result->isEmpty()) {
            return $this->handleError($request->withStatusCode(self::STATUS_RANGE_NOT_SATISFIABLE));
        }

        return $request->withResponder(
            [SearchResponder::class, [':result' => $result, ':range' => $payload['range']]]
        );
    }

    public function getValidator(DaikonRequest $request): ?ValidatorInterface
    {
        return $this->requestValidator
            ->error('range', RangeValidator::class, ['required' => false, 'default' => Range::fromNative([0, 9])]);
    }

    public function isAuthorized(DaikonRequest $request): bool
    {
        $role = $request->getAttribute(JwtAuthenticator::AUTHENTICATOR);
        return $this->authorizationService->isAllowed($role, $this, 'payment.search');
    }
}
