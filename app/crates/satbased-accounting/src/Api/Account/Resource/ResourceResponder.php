<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Account\Resource;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Boot\Middleware\Action\SerializerInterface;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Accounting\ReadModel\Standard\Account;

final class ResourceResponder extends Responder
{
    private Account $account;

    private SerializerInterface $serializer;

    public function __construct(Account $account, SerializerInterface $serializer)
    {
        $this->account = $account;
        $this->serializer = $serializer;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new TextResponse(
            $this->serializer->serialize($this->account, 'json'),
            self::STATUS_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
