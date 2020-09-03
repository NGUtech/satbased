<?php declare(strict_types=1);

namespace Satbased\Accounting\Api\Payment\Search;

use Daikon\Interop\Assertion;
use Daikon\ReadModel\Query\QueryInterface;
use Satbased\Security\ValueObject\ProfileId;

final class SearchQuery implements QueryInterface
{
    private array $query;

    /** @param array $query */
    public static function fromNative($query): QueryInterface
    {
        Assertion::isArray($query);

        return new self($query);
    }

    public function toNative(): array
    {
        return $this->query;
    }

    public static function build(array $query = []): self
    {
        return new self($query);
    }

    /** @param null|string|ProfileId $profileId */
    public function withProfileId($profileId = null): self
    {
        if (!is_null($profileId)) {
            $this->query['query']['bool']['filter'][] = ['term' => ['profileId' => (string)$profileId]];
        }

        return $this;
    }

    public function withState(string $state = null): self
    {
        if (!is_null($state)) {
            $this->query['query']['bool']['filter'][] = ['term' => ['state' => $state]];
        }

        return $this;
    }

    public function withReferences(array $references = []): self
    {
        foreach ($references as $key => $reference) {
            $this->withReference($key, $reference);
        }

        return $this;
    }

    public function withReference(string $key, string $reference = null): self
    {
        if (!is_null($reference)) {
            $this->query['query']['bool']['filter'][] = ['term' => ['references.'.$key => $reference]];
        }

        return $this;
    }

    public function withSort(string $field, string $order = 'asc'): self
    {
        $this->query['sort'][] = [$field => $order];

        return $this;
    }

    protected function __construct(array $query = [])
    {
        $this->query = $query;
    }
}
