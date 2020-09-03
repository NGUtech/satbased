<?php declare(strict_types=1);

namespace Satbased\Security\Api\Profile\Me;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Boot\Middleware\Action\SerializerInterface;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Satbased\Security\ReadModel\Standard\Profile;

final class MeResponder extends Responder
{
    private Profile $profile;

    private SerializerInterface $serializer;

    public function __construct(Profile $profile, SerializerInterface $serializer)
    {
        $this->profile = $profile;
        $this->serializer = $serializer;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new TextResponse(
            $this->serializer->serialize($this->profile, 'json'),
            self::STATUS_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
