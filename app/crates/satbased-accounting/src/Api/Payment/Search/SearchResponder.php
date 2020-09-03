<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Search;

use Daikon\Boot\Middleware\Action\DaikonRequest;
use Daikon\Boot\Middleware\Action\Responder;
use Daikon\Boot\Middleware\Action\SerializerInterface;
use Daikon\ReadModel\Storage\StorageResultInterface;
use Daikon\ValueObject\Range;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

final class SearchResponder extends Responder
{
    private StorageResultInterface $result;

    private Range $range;

    private SerializerInterface $serializer;

    public function __construct(StorageResultInterface $result, Range $range, SerializerInterface $serializer)
    {
        $this->result = $result;
        $this->range = $range;
        $this->serializer = $serializer;
    }

    public function respondToJson(DaikonRequest $request): ResponseInterface
    {
        return new TextResponse(
            $this->serializer->serialize($this->result->getProjectionMap(), 'json'),
            self::STATUS_OK,
            [
                'Content-Type' => 'application/json',
                'Vary' => 'Content-Range',
                'Content-Range' => sprintf(
                    'payments %d-%d/%s',
                    $this->range->getStart(),
                    $this->result->count()-1,
                    $this->result->getMetadata()->get('total', '$')
                )
            ]
        );
    }
}
