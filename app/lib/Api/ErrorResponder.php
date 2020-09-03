<?php declare(strict_types=1);

namespace Satbased\Api;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Boot\Validator\DaikonRequestValidator;
use Exception;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

final class ErrorResponder extends Responder
{
    private DaikonRequestValidator $requestValidator;

    public function __construct(DaikonRequestValidator $requestValidator)
    {
        $this->requestValidator = $requestValidator;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        $errors = $request->getErrors();
        $statusCode = $request->getStatusCode(self::STATUS_INTERNAL_SERVER_ERROR);

        $errorReport = $this->requestValidator->getValidationReport()->getErrors();
        if (!$errorReport->isEmpty()) {
            $errors = $errorReport->without(0)->getMessages(); // removing $ error messages
            $statusCode = $errorReport->getStatusCode() ?? $statusCode;
        }

        if ($errors instanceof Exception) {
            $errors = ['$' => [$errors->getMessage()]];
        }

        $response = !empty($errors) ? new JsonResponse(['errors' => $errors]) : new EmptyResponse;

        return $response->withStatus($statusCode);
    }
}
