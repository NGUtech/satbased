<?php declare(strict_types=1);

namespace Satbased\Security\ReadModel\Standard;

use Daikon\Elasticsearch7\Query\TermFilter;
use Daikon\Elasticsearch7\ReadModel\Elasticsearch7Collection;
use Daikon\ReadModel\Repository\RepositoryMap;
use Daikon\ReadModel\Storage\StorageResultInterface;
use Satbased\Security\ValueObject\ProfileId;

final class ProfileCollection extends Elasticsearch7Collection
{
    public function __construct(RepositoryMap $repositoryMap)
    {
        $this->repository = $repositoryMap->get(ProfileId::PREFIX.'.standard');
    }

    public function byEmail(string $email): StorageResultInterface
    {
        return $this->selectOne(TermFilter::fromNative(['email' => $email]));
    }
}
