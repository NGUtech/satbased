<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Resource;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Boot\Middleware\Action\SerializerInterface;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Accounting\ReadModel\Standard\Payment;

final class ResourceResponder extends Responder
{
    private Payment $payment;

    private SerializerInterface $serializer;

    public function __construct(Payment $payment, SerializerInterface $serializer)
    {
        $this->payment = $payment;
        $this->serializer = $serializer;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new TextResponse(
            $this->serializer->serialize($this->payment, 'json'),
            self::STATUS_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
