<?php declare(strict_types=1);

namespace Satbased\Accounting\ReadModel\Standard;

use Daikon\Elasticsearch7\ReadModel\Elasticsearch7Collection;
use Daikon\ReadModel\Repository\RepositoryMap;
use Satbased\Accounting\ValueObject\AccountId;

final class AccountCollection extends Elasticsearch7Collection
{
    public function __construct(RepositoryMap $repositoryMap)
    {
        $this->repository = $repositoryMap->get(AccountId::PREFIX.'.standard');
    }
}
