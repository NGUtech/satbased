<?php declare(strict_types=1);

namespace Satbased\Security\ReadModel\Standard;

use Daikon\Entity\EntityInterface;
use Daikon\ReadModel\Projection\ProjectionInterface;
use Daikon\ReadModel\Query\QueryInterface;
use Daikon\ReadModel\Repository\RepositoryInterface;
use Daikon\ReadModel\Storage\SearchAdapterInterface;
use Daikon\ReadModel\Storage\ScrollAdapterInterface;
use Daikon\ReadModel\Storage\StorageAdapterInterface;
use Daikon\ReadModel\Storage\StorageResultInterface;

final class ProfileRepository implements RepositoryInterface
{
    /** @var StorageAdapterInterface|SearchAdapterInterface|ScrollAdapterInterface */
    private $storageAdapter;

    public function __construct(StorageAdapterInterface $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
    }

    public function findById(string $identifier): StorageResultInterface
    {
        return $this->storageAdapter->read($identifier);
    }

    public function findByIds(array $identifiers): StorageResultInterface
    {
    }

    public function search(QueryInterface $query, int $from = null, int $size = null): StorageResultInterface
    {
        return $this->storageAdapter->search($query, $from, $size);
    }

    /** @param Profile $profile */
    public function persist(ProjectionInterface $profile): bool
    {
        return $this->storageAdapter->write(
            (string)$profile->getProfileId(),
            (array)$profile->toNative()
        );
    }

    public function walk(QueryInterface $query, callable $callback, int $size = null): void
    {
        $storageResult = $this->storageAdapter->scrollStart($query, $size);
        $projections = $storageResult->getProjectionMap();
        $cursor = $storageResult->getMetadata()->get('cursor');

        do {
            array_walk($projections->unwrap(), $callback);
            $storageResult = $this->storageAdapter->scrollNext($cursor);
            $projections = $storageResult->getProjectionMap();
            $cursor = $storageResult->getMetadata()->get('cursor');
        } while (!$projections->isEmpty());

        $this->storageAdapter->scrollEnd($cursor);
    }

    public function makeProjection(): Profile
    {
        return Profile::fromNative([EntityInterface::TYPE_KEY => Profile::class]);
    }
}
