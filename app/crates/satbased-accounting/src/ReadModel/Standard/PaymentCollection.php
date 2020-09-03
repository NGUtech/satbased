<?php declare(strict_types=1);

namespace Satbased\Accounting\ReadModel\Standard;

use Daikon\Elasticsearch7\ReadModel\Elasticsearch7Collection;
use Daikon\ReadModel\Repository\RepositoryMap;
use Satbased\Accounting\ValueObject\PaymentId;

final class PaymentCollection extends Elasticsearch7Collection
{
    public function __construct(RepositoryMap $repositoryMap)
    {
        $this->repository = $repositoryMap->get(PaymentId::PREFIX.'.standard');
    }
}
